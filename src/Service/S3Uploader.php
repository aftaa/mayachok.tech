<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Credentials\Credentials;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class S3Uploader
{
    private S3Client $client;
    private string $bucket;

    public function __construct(ParameterBagInterface $params)
    {
        $key = $params->get('s3.key');
        $secret = $params->get('s3.secret');
        $endpoint = $params->get('s3.endpoint');
        $region = $params->get('s3.region');
        $this->bucket = $params->get('s3.bucket');

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'endpoint' => $endpoint,
            'credentials' => new Credentials($key, $secret),
        ]);
    }

    public function upload(string $key, string $localPath): void
    {
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'SourceFile' => $localPath,
        ]);
    }

    public function getPublicUrl(?string $key): string
    {
        if (null === $key) {
            return '';
        }

        return $this->client->getObjectUrl($this->bucket, $key);
    }
}
