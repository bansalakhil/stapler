<?php

namespace Hollogram\Stapler\Factories;

use Hollogram\Stapler\Attachment as AttachedFile;
use Hollogram\Stapler\Storage\Filesystem;
use Hollogram\Stapler\Storage\S3;
use Hollogram\Stapler\Stapler;

class Storage
{
    /**
     * Build a storage instance.
     *
     * @param AttachedFile $attachment
     *
     * @return \Hollogram\Stapler\Storage\StorageableInterface
     */
    public static function create(AttachedFile $attachment)
    {
        switch ($attachment->storage) {
            case 'filesystem':
                return new Filesystem($attachment);
                break;

            case 's3':
                $s3Client = Stapler::getS3ClientInstance($attachment);

                return new S3($attachment, $s3Client);
                break;

            default:
                return new Filesystem($attachment);
                break;
        }
    }
}
