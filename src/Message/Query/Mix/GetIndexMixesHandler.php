<?php

namespace App\Message\Query\Mix;

use App\Entity\Mix;
use App\Repository\MixRepository;
use App\Service\S3Uploader;
use App\Specification\PublicMixesSpecification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
readonly class GetIndexMixesHandler
{
    public function __construct(
        private MixRepository $mixRepository,
        private S3Uploader    $s3Uploader,
    ) {
    }

    /**
     * @return list<Mix>
     */
    public function __invoke(GetIndexMixesQuery $query): array
    {
        $mixes = $this->mixRepository->findMatches(new PublicMixesSpecification());

        return array_map(function ($mix) {
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
    }
}
