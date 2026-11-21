<?php

namespace Kohana\Image;

use Kohana\Foundation\Application;
use Kohana\Foundation\Debug;
use Kohana\Foundation\KohanaException;
use Kohana\Log\Logger;

/**
 * Image manipulation support. Allows images to be resized, cropped, etc.
 *
 * @package    Kohana/Image
 * @category   Base
 * @author     Kohana Team
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    https://kohana.top/license.html
 */
abstract class Image
{
    // Resizing constraints
    public const NONE = 0x01;
    public const WIDTH = 0x02;
    public const HEIGHT = 0x03;
    public const AUTO = 0x04;
    public const INVERSE = 0x05;
    public const PRECISE = 0x06;
    // Flipping directions
    public const HORIZONTAL = 0x11;
    public const VERTICAL = 0x12;

    // Status of the driver check
    protected static $checked = false;

    /**
     * Loads an image and prepares it for manipulation.
     *
     *     $image = Image::factory('upload/test.jpg');
     *
     * @param string $file image file path
     * @param string|null $driver Driver type: GD, ImageMagick, etc.
     * @return  \Kohana\Image\Image
     * @throws \Kohana\Foundation\KohanaException
     */
    public static function factory(string $file, ?string $driver = null): self
    {
        if ($driver === null) {
            // Use the driver from configuration file
            $driver = Application::$config->load('image.default_driver');
        }

        if (!isset($driver)) {
            throw new KohanaException('Image driver not defined');
        }

        // Set the class name
        $class = str_contains($driver, '\\') ? $driver : __NAMESPACE__ . '\\' . ucfirst(strtolower($driver)) . 'Image';

        return new $class($file);
    }

    /**
     * @var  string  image file path
     */
    public $file;

    /**
     * @var int image width
     */
    public $width;

    /**
     * @var int image height
     */
    public $height;

    /**
     * @var int one of the IMAGETYPE_* constants
     */
    public $type;

    /**
     * @var  string  mime type of the image
     */
    public $mime;

    /**
     * Loads information about the image. Will throw an exception if the image
     * does not exist or is not an image.
     *
     * @param string $file Image file path
     * @return  void
     * @throws  \Kohana\Foundation\KohanaException
     */
    public function __construct(string $file)
    {
        try {
            // Get the real path to the file
            $file = realpath($file);

            // Get the image information
            $info = getimagesize($file);
        } catch (\Exception $e) {
            // Ignore all errors while reading the image
        }

        if (empty($file) || empty($info)) {
            throw new KohanaException('Not an image or invalid image: :file', [':file' => Debug::path($file)]);
        }

        // Store the image information
        $this->file = $file;
        $this->width = $info[0];
        $this->height = $info[1];
        $this->type = $info[2];
        $this->mime = image_type_to_mime_type($this->type);
    }

    /**
     * Render the current image.
     *
     *     echo $image;
     *
     * [!!] The output of this function is binary and must be rendered with the
     * appropriate Content-Type header, or it will not be displayed correctly!
     *
     * @return  string
     */
    public function __toString()
    {
        try {
            // Render the current image
            return $this->render();
        } catch (\Exception $e) {
            if (is_object(Application::$log)) {
                // Get the text of the exception
                $error = KohanaException::text($e);

                // Add this exception to the log
                Application::$log->add(Logger::ERROR, $error);
            }

            // Showing any kind of error will be "inside" image data
            return '';
        }
    }

    /**
     * Resize the image to the given size. Either the width or the height can
     * be omitted and the image will be resized proportionally.
     *
     *     // Resize to 200 pixels on the shortest side
     *     $image->resize(200, 200);
     *
     *     // Resize to 200x200 pixels, keeping aspect ratio
     *     $image->resize(200, 200, Image::INVERSE);
     *
     *     // Resize to 500 pixel width, keeping aspect ratio
     *     $image->resize(500, null);
     *
     *     // Resize to 500 pixel height, keeping aspect ratio
     *     $image->resize(null, 500);
     *
     *     // Resize to 200x500 pixels, ignoring aspect ratio
     *     $image->resize(200, 500, Image::NONE);
     *
     * @param int|null $width   New width
     * @param int|null $height  New height
     * @param int|null $master  Master dimension
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_resize
     */
    public function resize(?int $width = null, ?int $height = null, ?int $master = null): self
    {
        if ($master === null) {
            // Choose the master dimension automatically
            $master = static::AUTO;
        }
        // Image::WIDTH and Image::HEIGHT deprecated. You can use it in old projects,
        // but in new you must pass empty value for non-master dimension
        elseif ($master === static::WIDTH && !empty($width)) {
            $master = static::AUTO;

            // Set empty height for backward compatibility
            $height = null;
        } elseif ($master === static::HEIGHT && !empty($height)) {
            $master = static::AUTO;

            // Set empty width for backward compatibility
            $width = null;
        }

        if (empty($width)) {
            if ($master === static::NONE) {
                // Use the current width
                $width = $this->width;
            } else {
                // If width not set, master will be height
                $master = static::HEIGHT;
            }
        }

        if (empty($height)) {
            if ($master === static::NONE) {
                // Use the current height
                $height = $this->height;
            } else {
                // If height not set, master will be width
                $master = static::WIDTH;
            }
        }

        switch ($master) {
            case static::AUTO:
                // Choose direction with the greatest reduction ratio
                $master = $this->width / $width > $this->height / $height ? static::WIDTH : static::HEIGHT;
                break;
            case static::INVERSE:
                // Choose direction with the minimum reduction ratio
                $master = $this->width / $width > $this->height / $height ? static::HEIGHT : static::WIDTH;
                break;
        }

        switch ($master) {
            case static::WIDTH:
                // Recalculate the height based on the width proportions
                $height = $this->height * $width / $this->width;
                break;
            case static::HEIGHT:
                // Recalculate the width based on the height proportions
                $width = $this->width * $height / $this->height;
                break;
            case static::PRECISE:
                // Resize to precise size
                $ratio = $this->width / $this->height;

                if ($width / $height > $ratio) {
                    $height = $this->height * $width / $this->width;
                } else {
                    $width = $this->width * $height / $this->height;
                }
                break;
        }

        // Convert the width and height to integers, minimum value is 1px
        $width = max(round($width), 1);
        $height = max(round($height), 1);

        $this->doResize($width, $height);

        return $this;
    }

    /**
     * Crop an image to the given size. Either the width or the height can be
     * omitted and the current width or height will be used.
     *
     * If no offset is specified, the center of the axis will be used.
     * If an offset of true is specified, the bottom of the axis will be used.
     *
     *     // Crop the image to 200x200 pixels, from the center
     *     $image->crop(200, 200);
     *
     * @param int $width New width
     * @param int $height New height
     * @param   mixed    $offset_x  offset from the left
     * @param   mixed    $offset_y  offset from the top
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_crop
     */
    public function crop(int $width, int $height, $offset_x = null, $offset_y = null): self
    {
        if ($width > $this->width) {
            // Use the current width
            $width = $this->width;
        }

        if ($height > $this->height) {
            // Use the current height
            $height = $this->height;
        }

        if ($offset_x === null) {
            // Center the X offset
            $offset_x = round(($this->width - $width) / 2);
        } elseif ($offset_x === true) {
            // Bottom the X offset
            $offset_x = $this->width - $width;
        } elseif ($offset_x < 0) {
            // Set the X offset from the right
            $offset_x = $this->width - $width + $offset_x;
        }

        if ($offset_y === null) {
            // Center the Y offset
            $offset_y = round(($this->height - $height) / 2);
        } elseif ($offset_y === true) {
            // Bottom the Y offset
            $offset_y = $this->height - $height;
        } elseif ($offset_y < 0) {
            // Set the Y offset from the bottom
            $offset_y = $this->height - $height + $offset_y;
        }

        // Determine the maximum possible width and height
        $max_width = $this->width - $offset_x;
        $max_height = $this->height - $offset_y;

        if ($width > $max_width) {
            // Use the maximum available width
            $width = $max_width;
        }

        if ($height > $max_height) {
            // Use the maximum available height
            $height = $max_height;
        }

        $this->doCrop($width, $height, $offset_x, $offset_y);

        return $this;
    }

    /**
     * Rotate the image by a given amount.
     *
     *     // Rotate 45 degrees clockwise
     *     $image->rotate(45);
     *
     *     // Rotate 90% counter-clockwise
     *     $image->rotate(-90);
     *
     * @param int $degrees Degrees to rotate: -360-360
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_rotate
     */
    public function rotate(int $degrees): self
    {
        if ($degrees > 180) {
            do {
                // Keep subtracting full circles until the degrees have normalized
                $degrees -= 360;
            } while ($degrees > 180);
        }

        if ($degrees < -180) {
            do {
                // Keep adding full circles until the degrees have normalized
                $degrees += 360;
            } while ($degrees < -180);
        }

        $this->doRotate($degrees);

        return $this;
    }

    /**
     * Flip the image along the horizontal or vertical axis.
     *
     *     // Flip the image from top to bottom
     *     $image->flip(Image::HORIZONTAL);
     *
     *     // Flip the image from left to right
     *     $image->flip(Image::VERTICAL);
     *
     * @param int $direction Direction: Image::HORIZONTAL, Image::VERTICAL
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_flip
     */
    public function flip(int $direction): self
    {
        if ($direction !== static::HORIZONTAL) {
            // Flip vertically
            $direction = static::VERTICAL;
        }

        $this->doFlip($direction);

        return $this;
    }

    /**
     * Sharpen the image by a given amount.
     *
     *     // Sharpen the image by 20%
     *     $image->sharpen(20);
     *
     * @param int $amount Amount to sharpen: 1-100
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_sharpen
     */
    public function sharpen(int $amount): self
    {
        // The amount must be in the range of 1 to 100
        $amount = min(max($amount, 1), 100);

        $this->doSharpen($amount);

        return $this;
    }

    /**
     * Add a reflection to an image. The most opaque part of the reflection
     * will be equal to the opacity setting and fade out to full transparent.
     * Alpha transparency is preserved.
     *
     *     // Create a 50 pixel reflection that fades from 0-100% opacity
     *     $image->reflection(50);
     *
     *     // Create a 50 pixel reflection that fades from 100-0% opacity
     *     $image->reflection(50, 100, true);
     *
     *     // Create a 50 pixel reflection that fades from 0-60% opacity
     *     $image->reflection(50, 60, true);
     *
     * [!!] By default, the reflection will go from transparent at the top
     * to opaque at the bottom.
     *
     * @param int|null $height Reflection height
     * @param int $opacity Reflection opacity: 0-100
     * @param bool $fade_in True to fade in, false to fade out
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_reflection
     */
    public function reflection(?int $height = null, int $opacity = 100, bool $fade_in = false): self
    {
        if ($height === null || $height > $this->height) {
            // Use the current height
            $height = $this->height;
        }

        // The opacity must be in the range of 0 to 100
        $opacity = min(max($opacity, 0), 100);

        $this->doReflection($height, $opacity, $fade_in);

        return $this;
    }

    /**
     * Add a watermark to an image with a specified opacity. Alpha transparency
     * will be preserved.
     *
     * If no offset is specified, the center of the axis will be used.
     * If an offset of true is specified, the bottom of the axis will be used.
     *
     *     // Add a watermark to the bottom right of the image
     *     $mark = Image::factory('upload/watermark.png');
     *     $image->watermark($mark, true, true);
     *
     * @param   \Kohana\Image\Image    $watermark  watermark Image instance
     * @param int|true|null $offset_x Offset from the left
     * @param int|true|null $offset_y Offset from the top
     * @param int $opacity Opacity of watermark: 1-100
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_watermark
     */
    public function watermark(self $watermark, $offset_x = null, $offset_y = null, int $opacity = 100): self
    {
        if ($offset_x === null) {
            // Center the X offset
            $offset_x = round(($this->width - $watermark->width) / 2);
        } elseif ($offset_x === true) {
            // Right the X offset
            $offset_x = $this->width - $watermark->width;
        } elseif ($offset_x < 0) {
            // Set the X offset from the right
            $offset_x = $this->width - $watermark->width + $offset_x;
        }

        if ($offset_y === null) {
            // Center the Y offset
            $offset_y = round(($this->height - $watermark->height) / 2);
        } elseif ($offset_y === true) {
            // Bottom the Y offset
            $offset_y = $this->height - $watermark->height;
        } elseif ($offset_y < 0) {
            // Set the Y offset from the bottom
            $offset_y = $this->height - $watermark->height + $offset_y;
        }

        // The opacity must be in the range of 1 to 100
        $opacity = min(max($opacity, 1), 100);

        $this->doWatermark($watermark, $offset_x, $offset_y, $opacity);

        return $this;
    }

    /**
     * Set the background color of an image. This is only useful for images
     * with alpha transparency.
     *
     *     // Make the image background black
     *     $image->background('#000');
     *
     *     // Make the image background black with 50% opacity
     *     $image->background('#000', 50);
     *
     * @param string $color Hexadecimal color value
     * @param int $opacity Background opacity: 0-100
     * @return  $this
     * @uses    \Kohana\Image\Image::_do_background
     */
    public function background(string $color, int $opacity = 100): self
    {
        if ($color[0] === '#') {
            // Remove the pound
            $color = substr($color, 1);
        }

        if (strlen($color) === 3) {
            // Convert shorthand into longhand hex notation
            $color = preg_replace('/./', '$0$0', $color);
        }

        // Convert the hex into RGB values
        list($r, $g, $b) = array_map('hexdec', str_split($color, 2));

        // The opacity must be in the range of 0 to 100
        $opacity = min(max($opacity, 0), 100);

        $this->doBackground($r, $g, $b, $opacity);

        return $this;
    }

    /**
     * Save the image. If the filename is omitted, the original image will
     * be overwritten.
     *
     *     // Save the image as a PNG
     *     $image->save('saved/cool.png');
     *
     *     // Overwrite the original image
     *     $image->save();
     *
     * [!!] If the file exists, but is not writable, an exception will be thrown.
     *
     * [!!] If the file does not exist, and the directory is not writable, an
     * exception will be thrown.
     *
     * @param string|null $file New image path
     * @param int $quality Quality of image: 1-100
     * @return  bool
     * @throws  \Kohana\Foundation\KohanaException
     * @uses    \Kohana\Image\Image::_save
     */
    public function save(?string $file = null, int $quality = 100): bool
    {
        if ($file === null) {
            // Overwrite the file
            $file = $this->file;
        }

        if (is_file($file)) {
            if (!is_writable($file)) {
                throw new KohanaException('File must be writable: :file', [':file' => Debug::path($file)]);
            }
        } else {
            // Get the directory of the file
            $directory = realpath(pathinfo($file, PATHINFO_DIRNAME));

            if (!is_dir($directory) || !is_writable($directory)) {
                throw new KohanaException('Directory must be writable: :directory', [':directory' => Debug::path($directory)]);
            }
        }

        // The quality must be in the range of 1 to 100
        $quality = min(max($quality, 1), 100);

        return $this->doSave($file, $quality);
    }

    /**
     * Render the image and return the binary string.
     *
     *     // Render the image at 50% quality
     *     $data = $image->render(null, 50);
     *
     *     // Render the image as a PNG
     *     $data = $image->render('png');
     *
     * @param string|null $type Image type to return: png, jpg, gif, etc.
     * @param int $quality Quality of image: 1-100
     * @return  string
     * @uses    \Kohana\Image\Image::_do_render
     */
    public function render(?string $type = null, int $quality = 100): string
    {
        if ($type === null) {
            // Use the current image type
            $type = image_type_to_extension($this->type, false);
        }

        return $this->doRender($type, $quality);
    }

    /**
     * Execute a resize.
     *
     * @param int $width   New width
     * @param int $height  New height
     * @return  void
     */
    abstract protected function doResize(int $width, int $height);
    /**
     * Execute a crop.
     *
     * @param int $width     New width
     * @param int $height    New height
     * @param int $offset_x  Offset from the left
     * @param int $offset_y  Offset from the top
     * @return  void
     */
    abstract protected function doCrop(int $width, int $height, int $offset_x, int $offset_y);
    /**
     * Execute a rotation.
     *
     * @param int $degrees Degrees to rotate
     * @return  void
     */
    abstract protected function doRotate(int $degrees);
    /**
     * Execute a flip.
     *
     * @param int $direction Direction to flip
     * @return  void
     */
    abstract protected function doFlip(int $direction);
    /**
     * Execute a sharpening.
     *
     * @param int $amount Amount to sharpen
     * @return  void
     */
    abstract protected function doSharpen(int $amount);
    /**
     * Execute a reflection.
     *
     * @param int $height Reflection height
     * @param int $opacity Reflection opacity
     * @param bool $fade_in True to fade in, false to fade out
     * @return  void
     */
    abstract protected function doReflection(int $height, int $opacity, bool $fade_in);
    /**
     * Execute a watermarking.
     *
     * @param   \Kohana\Image\Image    $image     watermarking Image
     * @param int $offset_x Offset from the left
     * @param int $offset_y Offset from the top
     * @param int $opacity Opacity of watermark
     * @return  void
     */
    abstract protected function doWatermark(self $image, int $offset_x, int $offset_y, int $opacity);
    /**
     * Execute a background.
     *
     * @param int $r        Red
     * @param int $g        Green
     * @param int $b        Blue
     * @param int $opacity  Opacity
     * @return void
     */
    abstract protected function doBackground(int $r, int $g, int $b, int $opacity);
    /**
     * Execute a save.
     *
     * @param string $file New image filename
     * @param int $quality Quality
     * @return  bool
     */
    abstract protected function doSave(string $file, int $quality): bool;
    /**
     * Execute a render.
     *
     * @param string $type Image type: png, jpg, gif, etc.
     * @param int $quality Quality
     * @return  string
     */
    abstract protected function doRender(string $type, int $quality): string;
}
