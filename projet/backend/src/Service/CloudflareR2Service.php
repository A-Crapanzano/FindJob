<?php

namespace App\Service;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudflareR2Service
{
    private S3Client $s3Client;
    private string $bucket;
    private string $publicUrl;

    public function __construct(
        string $accountId,
        string $accessKeyId,
        string $secretAccessKey,
        string $bucket,
        string $publicUrl
    ) {
        $this->bucket = $bucket;
        $this->publicUrl = rtrim($publicUrl, '/');

        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => sprintf('https://%s.r2.cloudflarestorage.com', $accountId),
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
            'use_path_style_endpoint' => false,
        ]);
    }

    public function uploadFile(UploadedFile $file, string $directory = 'resumes'): array
    {
        try {
            $maxSize = 5 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return [
                    'success' => false,
                    'url' => null,
                    'key' => null,
                    'error' => 'Le fichier ne doit pas dépasser 5 MB'
                ];
            }

            $allowedMimeTypes = [
                'application/pdf',
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                return [
                    'success' => false,
                    'url' => null,
                    'key' => null,
                    'error' => 'Format de fichier non autorisé. Seuls les PDF sont acceptés'
                ];
            }

            $extension = $file->guessExtension();
            $fileName = sprintf(
                '%s_%s.%s',
                uniqid('cv_', true),
                bin2hex(random_bytes(8)),
                $extension
            );

            $key = sprintf('%s/%s', trim($directory, '/'), $fileName);

            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => fopen($file->getPathname(), 'rb'),
                'ContentType' => $file->getMimeType(),
                'Metadata' => [
                    'original-name' => $file->getClientOriginalName(),
                    'uploaded-at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ],
            ]);

            // Ne pas stocker l'URL publique, juste la clé
            return [
                'success' => true,
                'url' => null, // On ne stocke plus l'URL
                'key' => $key,
                'error' => null
            ];
        } catch (AwsException $e) {
            return [
                'success' => false,
                'url' => null,
                'key' => null,
                'error' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'url' => null,
                'key' => null,
                'error' => 'Erreur inattendue: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Génère une URL signée temporaire pour accéder au fichier
     * 
     * @param string $key La clé du fichier dans R2
     * @param int $expiresIn Durée de validité en secondes (par défaut 1 heure)
     * @return string URL signée temporaire
     */
    public function getSignedUrl(string $key, int $expiresIn = 3600): string
    {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, "+{$expiresIn} seconds");
            return (string) $request->getUri();
        } catch (\Exception $e) {
            error_log('Erreur génération URL signée: ' . $e->getMessage());
            return '';
        }
    }

    public function deleteFile(string $key): bool
    {
        try {
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]);
            return true;
        } catch (AwsException $e) {
            error_log('Erreur suppression R2: ' . $e->getMessage());
            return false;
        }
    }

    public function getPublicUrl(string $key): string
    {
        return sprintf('%s/%s', $this->publicUrl, ltrim($key, '/'));
    }
}
