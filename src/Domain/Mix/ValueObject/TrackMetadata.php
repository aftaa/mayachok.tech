<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class TrackMetadata
{
    private MixTitle $title;
    private ArtistName $artist;

    public function __construct(MixTitle $title, ArtistName $artist)
    {
        $this->title = $title;
        $this->artist = $artist;
    }

    public function getTitle(): MixTitle
    {
        return $this->title;
    }

    public function getArtist(): ArtistName
    {
        return $this->artist;
    }

    public function getTitleString(): string
    {
        return $this->title->toString();
    }

    public function getArtistString(): string
    {
        return $this->artist->toString();
    }

    public function equals(self $other): bool
    {
        return $this->title->equals($other->title)
            && $this->artist->equals($other->artist);
    }
}
