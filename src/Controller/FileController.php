<?php

namespace App\Controller;

use App\Repository\MixRepository;
use App\Service\S3Uploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FileController extends AbstractController
{
    #[Route('/api/stream/{uuid}', name: 'api_stream', methods: ['GET'])]
    public function streamAction(string $uuid, MixRepository $repo, S3Uploader $s3Uploader): JsonResponse
    {
        $mix = $repo->findOneByUuid($uuid);

        if (!$mix || !$mix->isProcessed()) {
            return $this->json(['error' => 'Микс не найден или еще не обработан'], 404);
        }

        // Проверка приватности
        if ($mix->isPrivate() && $mix->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Доступ запрещен'], 403);
        }

        // Генерируем подписанную ссылку на 1 час
        $url = $s3Uploader->getPresignedUrl($mix->getS3StreamKey(), 3600);

        return $this->json([
            'url' => $url,
            'expires_in' => 3600,
            'mix' => [
                'id' => $mix->getId(),
                'uuid' => $mix->getUuid(),
                'title' => $mix->getTitle(),
                'artist' => $mix->getArtist(),
                'duration' => $mix->getDuration(),
            ],
        ]);
    }

    #[Route('/api/download/{uuid}', name: 'api_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function downloadAction(string $uuid, MixRepository $repo, S3Uploader $s3Uploader): JsonResponse
    {
        $mix = $repo->findOneByUuid($uuid);

        if (!$mix || !$mix->isProcessed()) {
            return $this->json(['error' => 'Микс не найден или еще не обработан'], 404);
        }

        // Только владелец может скачать оригинал
        if ($mix->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Скачивание доступно только владельцу'], 403);
        }

        $url = $s3Uploader->getPresignedUrl($mix->getS3OriginalKey(), 3600);

        return $this->json([
            'url' => $url,
            'expires_in' => 3600,
            'filename' => $mix->getTitle() . '.mp3',
        ]);
    }
}
