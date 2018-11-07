<?php

namespace Hollogram\Stapler;

use Hollogram\Stapler\Interfaces\Validator as ValidatorInterface;

class Validator implements ValidatorInterface
{
    /**
     * @var
     */
    protected static $responseCode;
    
    /**
     * Validate the attachment options for an attachment type.
     * A url is required to have either an :id or an :id_partition interpolation.
     *
     * @param array $options
     */
    public function validateOptions(array $options)
    {
        $options['storage'] == 'filesystem' ? $this->validateFilesystemOptions($options) : $this->validateS3Options($options);
    }

    /**
     * Validate the attachment options for an attachment type when the storage
     * driver is set to 'filesystem'.
     *
     * @throws Exceptions\InvalidUrlOptionException
     *
     * @param array $options
     */
    protected function validateFilesystemOptions(array $options)
    {
        if (preg_match("/:id\b/", $options['url']) !== 1 && preg_match("/:id_partition\b/", $options['url']) !== 1 && preg_match("/:(secure_)?hash\b/", $options['url']) !== 1) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Url: an id, id_partition, hash, or secure_hash interpolation is required.', 1);
        }
    }

    /**
     * Validate the attachment options for an attachment type when the storage
     * driver is set to 's3'.
     *
     * @throws Exceptions\InvalidUrlOptionException
     *
     * @param array $options
     */
    protected function validateS3Options(array $options)
    {
        if (!$options['s3_object_config']['Bucket']) {
            throw new Exceptions\InvalidUrlOptionException('Invalid Path: a bucket is required for s3 storage.', 1);
        }

        if (self::$responseCode === null) {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_URL => 'http://169.254.169.254/latest/meta-data/iam/security-credentials/'
            ]);

            curl_exec($curl);

            self::$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);
        }

        if (self::$responseCode === 0) {
            if (!$options['s3_client_config']['secret']) {
                throw new Exceptions\InvalidUrlOptionException('Invalid Path: a secret is required for s3 storage.', 1);
            }

            if (!$options['s3_client_config']['key']) {
                throw new Exceptions\InvalidUrlOptionException('Invalid Path: a key is required for s3 storage.', 1);
            }
        }
    }
}
