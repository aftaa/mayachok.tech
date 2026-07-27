<?php

namespace App\Command\Mix;

use App\Entity\Mix;
use App\Repository\MixRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
final readonly class UploadHandler
{
    private const array ALLOWED_MIME_TYPES = ['audio/mpeg', 'audio/flac', 'audio/wav', 'audio/aiff', 'audio/x-wav'];

    public function __construct(
        private readonly MixRepository $mixRepository,
    ) {
    }

    /**
     * @throws UploadException
     *
     * @return array{int, string}
     */
    public function __invoke(UploadCommand $command): array
    {
        $uploadDir = $command->uploadDir;
        $this->checkUploadDir($uploadDir);

        $file = $command->file;
        $this->checkMimeTypes($file->getMimeType());

        $filename = uniqid() . '.' . $file->guessExtension();
        $file->move($uploadDir, $filename);

        $mix = new Mix();
        $mix->setTitle($command->title);
        $mix->setArtist($command->artist);
        $mix->setOriginalPath('/var/uploads/' . $filename);
        $mix->setIsProcessed(false);
        $mix->setUser($command->user);
        $this->mixRepository->save($mix);

        return [
            $mix->getId(),
            $mix->getOriginalPath(),
        ];
    }

    /**
     * @throws UploadException
     */
    private function checkUploadDir(string $uploadDir): void
    {
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new UploadException('Не могу создать папку' . $uploadDir);
            }
        }
    }

    /**
     * @throws UploadException
     */
    private function checkMimeTypes(string $mimeType): void
    {
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new UploadException('Недопустимый формат: ' . $mimeType);
        }
    }
}
