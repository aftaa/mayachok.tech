<?php

namespace App\Message\Command\Mix;

use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UploadCommand
{
    public function __construct(
        public string       $uploadDir,
        public UploadedFile $file,
        public User         $user,
        public string       $title,
        public string       $artist,
        public bool         $isPrivate,
    ) {
    }
}
