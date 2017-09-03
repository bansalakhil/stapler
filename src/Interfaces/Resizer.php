<?php

namespace Hollogram\Stapler\Interfaces;

use Imagine\Image\ImagineInterface;
use Hollogram\Stapler\Interfaces\File as FileInterface;
use Hollogram\Stapler\Interfaces\Style as StyleInterface;

interface Resizer
{
    /**
     * Constructor method.
     *
     * @param ImagineInterface $imagine
     */
    public function __construct(ImagineInterface $imagine);

    /**
     * Resize an image using the computed settings.
     *
     * @param FileInterface $file
     * @param Style         $style
     *
     * @return string
     */
    public function resize(FileInterface $file, StyleInterface $style);

    /**
     * Accessor method for the $imagine property.
     *
     * @param ImagineInterface $imagine
     */
    public function setImagine(ImagineInterface $imagine);
}