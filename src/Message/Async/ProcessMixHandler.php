<?php

namespace App\Message\Async;

use App\Repository\MixRepository;
use App\Service\S3Uploader;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ProcessMixHandler
{
    public function __construct(
        private MixRepository $repository,
        private LoggerInterface $logger,
        private HubInterface $hub,
        private ParameterBagInterface $parameterBag,
        private S3Uploader $s3Uploader,
    ) {
    }
    public function __invoke(ProcessMixMessage $message): void
    {
        $this->logger->info('Начинаем обработку микса №' . $message->mixId);
        $this->success($message->mixId, '🚀 Начинаем обработку', 5);

        $mix = $this->repository->find($message->mixId);
        if (null === $mix) {
            $this->error($message->mixId, 'Микс не найден в БД');
            return;
        }

        $filename = $this->parameterBag->get('kernel.project_dir') . $mix->getOriginalPath();
        if (!file_exists($filename)) {
            $this->error($message->mixId, 'Файл не найден: ' . $message->filename);
            return;
        }

        // Шаг 1: Конвертация (15% → 30%)
        $this->success($message->mixId, '⏳ Подготовка к конвертации...', 10);
        [$success, $mp3Path] = $this->convertToMp3($filename);
        if (!$success) {
            return;
        }

        // Шаг 2: Анализ (30% → 50%)
        try {
            $this->success($message->mixId, '⏳ Подготовка к анализу...', 35);
            $duration = $this->getDuration($mp3Path);
            $mix->setDuration($duration);
            $this->repository->save($mix);

            $peaksPath = $this->generatePeaks($mp3Path, $message->mixId);
            $this->success($message->mixId, '✅ Анализ завершен, загружаем в облако', 55);
        } catch (\Throwable $e) {
            $this->error($message->mixId, 'Ошибка анализа: ' . $e->getMessage());
            return;
        }

        // Шаг 3: Загрузка в S3 (55% → 90%)
        $this->success($message->mixId, '⏳ Загрузка оригинала...', 60);
        $this->s3Uploader->upload('originals/' . basename($filename), $filename);

        if (!$this->isMp3($filename)) {
            $this->success($message->mixId, '⏳ Загрузка MP3...', 70);
            $this->s3Uploader->upload('mp3/' . basename($mp3Path), $mp3Path);
        }

        $this->success($message->mixId, '⏳ Загрузка данных волны...', 80);
        $this->s3Uploader->upload('peaks/' . basename($peaksPath), $peaksPath);

        // Шаг 4: Обновление БД (90% → 100%)
        $this->success($message->mixId, '⏳ Сохранение данных...', 90);

        $mix->setS3OriginalKey('originals/' . basename($filename));
        $mix->setS3StreamKey('mp3/' . basename($mp3Path));
        $mix->setPeaksKey('peaks/' . basename($peaksPath));
        $mix->setIsProcessed(true);
        $this->repository->save($mix);

        // Шаг 5: Очистка
        unlink($filename);
        if ($filename !== $mp3Path) {
            unlink($mp3Path);
        }
        unlink($peaksPath);

        $this->success($message->mixId, '✅ Микс готов! 🎵', 100);
    }

    /**
     * @return array{bool, string}
     */
    private function convertToMp3(string $filename): array
    {
        if ($this->isMp3($filename)) {
            return [true, $filename];
        }

        $outputPath =
            dirname($filename) . DIRECTORY_SEPARATOR .
            pathinfo($filename, PATHINFO_FILENAME) . '.mp3';

        $command = sprintf(
            'ffmpeg -i %s -acodec mp3 -ab 320k %s 2>&1',
            escapeshellarg($filename),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);
        $this->logger->info(join("\n", $output));

        if (!$returnCode) {
            $this->logger->info('Файл конвертирован в mp3: ' . $outputPath);

            return [true, $outputPath];
        }
        $this->logger->error('Ошибка конвертации в mp3: ' . $outputPath);

        return [false, ''];
    }

    private function isMp3(string $filename): bool
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'mp3';
    }

    private function getDuration(string $filename): int
    {
        $command = sprintf(
            'ffprobe -i %s -show_entries format=duration -v quiet -of csv="p=0" 2>&1',
            escapeshellarg($filename)
        );

        $output = shell_exec($command);
        return (int) round((float) trim($output));
    }

    private function generatePeaks(string $filename): string
    {
        $outputPath = pathinfo($filename, PATHINFO_DIRNAME) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '.peaks.json';

        $command = sprintf(
            'audiowaveform -i %s -o %s --pixels-per-second 20 --bits 8 2>&1',
            escapeshellarg($filename),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException('Peak generation failed: ' . implode("\n", $output));
        }

        return $outputPath;
    }

    private function error(int $mixId, string $error): void
    {
        $this->logger->error($error);
        $update = new Update(
            'mix/progress/' . $mixId,
            json_encode([
                'error' => true,
                'status' => $error,
                'progress' => 0,
            ]),
        );
        $this->hub->publish($update);
    }

    private function success(int $mixId, string $success, int $progress): void
    {
        $this->hub->publish(new Update(
            'mix/progress/' . $mixId,
            json_encode([
                'error' => false,
                'status' => "(mix#$mixId) $success",
                'progress' => $progress,
            ]),
        ));
    }
}
