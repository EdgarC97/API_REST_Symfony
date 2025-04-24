<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageUploaderService
{
    private string $targetDirectory;

    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file): string
    {
        $filename = uniqid().'.'.$file->guessExtension();
        $file->move($this->getTargetDirectory(), $filename);

        return '/uploads/' . $filename;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
