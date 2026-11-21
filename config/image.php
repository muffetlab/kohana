<?php

return [
    /**
     * Driver used by Image::factory(): GD or Imagick.
     */
    'default_driver' => env('IMAGE_DRIVER', 'GD'),
];
