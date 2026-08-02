<?php

namespace App\Message\Query\Mix;

use App\Entity\Mix;
use App\Repository\MixRepository;
use App\Service\S3Uploader;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
readonly class GetMixShowHandler
{
    public function __construct(
        private MixRepository $mixRepository,
        private S3Uploader $s3Uploader,
    ) {
    }

    public function __invoke(GetMixShowQuery $query): array
    {
        $mix = $this->mixRepository->findOneByUuid($query->uuid);

        if (null === $mix) {
            return [];
        }

        return [
            'id' => $mix->getId(),
            'uuid' => $mix->getUuid(),
            'title' => $mix->getTitle(),
            'artist' => $mix->getArtist(),
            'duration' => $mix->getDuration(),
            's3StreamUrl' => $this->s3Uploader->getPublicUrl($mix->getS3StreamKey()),
            's3PeaksUrl' => $mix->getPeaksKey()
                ? $this->s3Uploader->getPublicUrl($mix->getPeaksKey())
                : null,
            'isProcessed' => $mix->isProcessed(),
        ];
    }
}
