<?php

namespace Hollogram\Stapler\Storage;

use Hollogram\Stapler\Interfaces\Storage as StorageInterface;
use Aws\S3\S3Client;
use Hollogram\Stapler\Attachment;

class S3 implements StorageInterface
{
    /**
     * The current attachedFile object being processed.
     *
     * @var \Hollogram\Stapler\Attachment
     */
    public $attachedFile;

    /**
     * The AWS S3Client instance.
     *
     * @var S3Client
     */
    protected $s3Client;

    /**
     * Boolean flag indicating if this attachment's bucket currently exists.
     *
     * @var array
     */
    protected $bucketExists = false;

    /**
     * Constructor method.
     *
     * @param Attachment $attachedFile
     * @param S3Client   $s3Client
     */
    public function __construct(Attachment $attachedFile, S3Client $s3Client)
    {
        $this->attachedFile = $attachedFile;
        $this->s3Client = $s3Client;
    }

    /**
     * Return the url for a file upload.
     *
     * @param string $styleName
     *
     * @return string
     */
    public function url($styleName)
    {
        // return $this->signedUrl($styleName);

        return $this->s3Client->getObjectUrl($this->attachedFile->s3_object_config['Bucket'], $this->path($styleName), null, ['PathStyle' => true]);
    }

    /**
     * Returns Signed URL for a file upload
     *
     * @param $styleName
     * @return string
     */
    // public function signedUrl($styleName)
    // {
    //     $command = $this->s3Client->getCommand('GetObject', [
    //         'Bucket' => $this->attachedFile->s3_object_config['Bucket'],
    //         'Key' => $this->path($styleName)
    //     ]);

    //     return $command->createPresignedUrl('+10 minutes');
    // }

    public function signedUrl($styleName, $time = "+1 hour")
    {
        $command = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->attachedFile->s3_object_config['Bucket'],
            'Key' => $this->path($styleName)
        ]);

        // return $command->createPresignedUrl('+10 minutes');

        $signedUrl = (string) $this->s3Client->createPresignedRequest($command, $time)->getUri();
        return $signedUrl;

    }

    /**
     * Returns an s3 file
     *
     * @param $styleName
     * @param $filePath
     * @return \Aws\Result
     */
    public function downloadFile($styleName, $filePath)
    {
        return $this->s3Client->getObject(array(
            'Bucket' => $this->attachedFile->s3_object_config['Bucket'],
            'Key'    => $this->path($styleName),
            'SaveAs' => $filePath
        ));
    }

    /**
     * Return the key the uploaded file object is stored under within a bucket.
     *
     * @param string $styleName
     *
     * @return string
     */
    public function path($styleName)
    {
        return $this->attachedFile->getInterpolator()->interpolate($this->attachedFile->path, $this->attachedFile, $styleName);
    }

    /**
     * Remove an attached file.
     *
     * @param array $filePaths
     */
    public function remove(array $filePaths)
    {
        if ($filePaths) {
            if (defined('Aws\S3\S3Client::LATEST_API_VERSION')) {
                $this->s3Client->deleteObjects(['Bucket' => $this->attachedFile->s3_object_config['Bucket'], 'Objects' => $this->getKeys($filePaths)]);
            } else {
                $this->s3Client->deleteObjects(['Bucket' => $this->attachedFile->s3_object_config['Bucket'], 'Delete' => ['Objects' => $this->getKeys($filePaths)]]);
            }
        }
    }

    /**
     * Move an uploaded file to it's intended destination.
     *
     * @param string $file
     * @param string $filePath
     */
    public function move($file, $filePath)
    {
        $objectConfig = $this->attachedFile->s3_object_config;
        $fileSpecificConfig = ['Key' => $filePath, 'SourceFile' => $file, 'ContentType' => $this->attachedFile->contentType()];
        $mergedConfig = array_merge($objectConfig, $fileSpecificConfig);

        $this->ensureBucketExists($mergedConfig['Bucket']);
        $this->s3Client->putObject($mergedConfig);

        @unlink($file);
    }

    /**
     * Return an array of paths (bucket keys) for an attachment.
     * There will be one path for each of the attachmetn's styles.
     *
     * @param  $filePaths
     *
     * @return array
     */
    protected function getKeys($filePaths)
    {
        $keys = [];

        foreach ($filePaths as $filePath) {
            $keys[] = ['Key' => $filePath];
        }

        return $keys;
    }

    /**
     * Ensure that a given S3 bucket exists.
     *
     * @param string $bucketName
     */
    protected function ensureBucketExists($bucketName)
    {
        if (!$this->bucketExists) {
            $this->buildBucket($bucketName);
        }
    }

    /**
     * Attempt to build a bucket (if it doesn't already exist).
     *
     * @param string $bucketName
     */
    protected function buildBucket($bucketName)
    {
        if (!$this->s3Client->doesBucketExist($bucketName, true)) {
            $this->s3Client->createBucket(['ACL' => $this->attachedFile->ACL, 'Bucket' => $bucketName, 'LocationConstraint' => $this->attachedFile->region]);
        }

        $this->bucketExists = true;
    }
}
