<?php

namespace App\Controller;

use App\Repository\MixRepository;
use App\Service\S3Uploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly MixRepository $mixRepository,
        private readonly S3Uploader $s3Uploader,
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        $mixes = $this->mixRepository->getAll();

        // Подготавливаем данные для каждого микса
        $mixesData = array_map(function($mix) {
            return [
                'id' => $mix->getId(),
                'title' => $mix->getTitle(),
                'artist' => $mix->getArtist(),
                'duration' => $mix->getDuration(),
                's3StreamUrl' => $this->s3Uploader->getPublicUrl($mix->getS3StreamKey()),
                's3PeaksUrl' => $mix->getPeaksKey()
                    ? $this->s3Uploader->getPublicUrl($mix->getPeaksKey())
                    : null,
                'isProcessed' => $mix->isProcessed(),
            ];
        }, $mixes);

        return $this->render('index/index.html.twig', [
            'mixes' => $mixesData
        ]);
    }
}
