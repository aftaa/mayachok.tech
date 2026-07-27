<?php

namespace App\MessageHandler;

use App\Message\ProcessMixMessage;
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

        // 1. Найти Mix
        // 2. Сконвертировать в MP3 (ffmpeg)
        // 3. Загрузить оригинал + MP3 + peaks в S3
        // 4. Обновить поля в Mix и сохранить
        // 5. Удалить временные файлы
        // 6. Отправить Mercure-уведомление о готовности

        $mix = $this->repository->find($message->mixId);

        if (null === $mix) {
            $this->error($message->mixId, 'Микс не найден в БД, номер микса ' . $message->mixId);

            return;
        }

        $filename = $this->parameterBag->get('kernel.project_dir') . $mix->getOriginalPath();
        if (!file_exists($filename)) {
            $this->error($message->mixId, 'Файл не найден' . $message->filename);

            return;
        }

        [$success, $mp3Path] = $this->convertToMp3($filename);
        if (!$success) {
            return;
        }
        $this->success($mix->getId(), 'Конвертация прошла успешно', 20);

        try {
            $duration = $this->getDuration($mp3Path);
            $peaksPath = $this->generatePeaks($mp3Path);

            $mix->setDuration($duration);
            $this->repository->save($mix);

           $this->success($mix->getId(),'Анализ микса завершен', 40);

            $this->s3Uploader->upload(
                'originals/' . basename($filename),
                $filename
            );

            $this->success($mix->getId(),'Загрузка в облако', 60);


            if (!$this->isMp3($filename)) {
                $this->s3Uploader->upload(
                    'mp3/' . basename($mp3Path),
                    $mp3Path
                );
            }

            $this->success($mix->getId(),'Загрузка в облако', 80);

            $this->s3Uploader->upload(
                'peaks/' . basename($peaksPath),
                $peaksPath
            );

            // Обновляем Mix
            $mix->setS3OriginalKey('originals/' . basename($filename));
            $mix->setS3StreamKey('mp3/' . basename($mp3Path));
            $mix->setPeaksKey('peaks/' . basename($peaksPath));
            $mix->setIsProcessed(true);
            $this->repository->save($mix);

            // Чистим временные файлы
            unlink($filename);
            if ($filename !== $mp3Path) {
                unlink($mp3Path);
            }
            unlink($peaksPath);

            $this->success($mix->getId(),'Микс обработан и загружен', 100);
        } catch (\Throwable $e) {
            $this->error($message->mixId, 'Ошибка анализа микса: ' . $e->getMessage());
        }
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
