<?php

/**
 *
 */
class CopterImages_Helper extends Phpr_Image
{

    /**
     * Creates a thumbnail
     * @param string $srcPath Specifies a sources image path
     * @param string $destPath Specifies a destination image path
     * @param mixed $destWidth Specifies a destination image width. Can have integer value or string 'auto'.
     * @param mixed $destHeight Specifies a destination image height. Can have integer value or string 'auto'.
     * @param string $mode Specifies a scaling mode. Possible values: keep_ratio, fit. It works only if both width and height are specified as numbers.
     * @param string $returnJpeg - returns JPEG (if true) or PNG image
     */
    public static function makeThumbnail($srcPath, $destPath, $destWidth, $destHeight, $forceGd = false, $mode = 'keep_ratio', $returnJpeg = true)
    {
        $extension = null;
        $pathInfo = pathinfo($srcPath);
        if (isset($pathInfo['extension']))
            $extension = strtolower($pathInfo['extension']);
            
        $allowedExtensions = array('gif', 'jpeg', 'jpg','png');
        if (!in_array($extension, $allowedExtensions))
            throw new Phpr_ApplicationException('Unknown image format');
            
        if (!preg_match('/^[0-9]*!?$/', $destWidth) && $destWidth != 'auto')
            throw new Phpr_ApplicationException("Invalid width specifier. Please use integer or 'auto' value.");

        if (!preg_match('/^[0-9]*!?$/', $destHeight) && $destHeight != 'auto')
            throw new Phpr_ApplicationException("Invalid height specifier. Please use integer or 'auto' value.");

        list($width_orig, $height_orig) = getimagesize($srcPath);
        $ratio_orig = $width_orig/$height_orig;

        $centerImage = false;

        if ($destWidth == 'auto' && $destHeight == 'auto')
        {
            $width = $width_orig;
            $height = $height_orig;
        }
        elseif ($destWidth == 'auto' && $destHeight != 'auto')
        {
            if (substr($destHeight, -1) == '!')
            {
                $destHeight = substr($destHeight, 0, -1);
                $height = $destHeight;
            }
            else
                $height = $height_orig > $destHeight ? $destHeight : $height_orig;

            $width = $height*$ratio_orig;
        } elseif ($destHeight == 'auto' && $destWidth != 'auto')
        {
            if (substr($destWidth, -1) == '!')
            {
                $destWidth = substr($destWidth, 0, -1);
                $width = $destWidth;
            }
            else
                $width = $width_orig > $destWidth ? $destWidth : $width_orig;

            $height = $width/$ratio_orig;
        }
        else
        {
            // Width and height specified as numbers
            if ($mode == 'keep_ratio') {

                if ($destWidth/$destHeight > $ratio_orig) {
                    $width = $destHeight*$ratio_orig;
                    $height = $destHeight;
                } else {
                    $height = $destWidth/$ratio_orig;
                    $width = $destWidth;
                }

                // No offset required
                $offset_x = 0;
                $offset_y = 0;
                
                $centerImage = true;
                $imgWidth = $destWidth;
                $imgHeight = $destHeight;

            } elseif ($mode==='force_crop') {

                /*
                 * If cropping is being forced, this determines the 
                 * starting coordinates to create the cropped image from 
                 * the center of the source image.
                 */
                $width = $destWidth;
                $height = $destHeight;
                $imgWidth = $destWidth;
                $imgHeight = $destHeight;

                // Determines the scale to use for resizing the image
                $scale = max(
                    $destWidth/$width_orig,
                    $destHeight/$height_orig
                );

                // Calculates the X/Y offset to center the cropped image
                // if ($width_orig>$height_orig) {
                $scaled_width = $width_orig*$scale;
                $scaled_height = $height_orig*$scale;
                $leftover_x = $scaled_width-$width;
                $leftover_y = $scaled_height-$height;
                $offset_x = round($leftover_x/2);
                $offset_y = round($leftover_y/2);

                // The original width must be adjusted to keep the 
                // image from being distorted
                $width_orig = $destWidth/$scale;
                $height_orig = $destHeight/$scale;

            } else {

                $height = $destHeight;
                $width = $destWidth;

                // No offset required
                $offset_x = 0;
                $offset_y = 0;

            }
        }

        if (!$centerImage) {
            $imgWidth = $width;
            $imgHeight = $height;
        }

        if (!Phpr::$config->get('IMAGEMAGICK_ENABLED') || $forceGd) {

            $image_p = imagecreatetruecolor($imgWidth, $imgHeight);

            $image = self::copter_create_image($extension, $srcPath);
            if ($image == null) {
                throw new Phpr_ApplicationException('Error loading the source image');
            }

            if (!$returnJpeg) {
                imagealphablending( $image_p, false );
                imagesavealpha( $image_p, true );
            }

            $white = imagecolorallocate($image_p, 255, 255, 255);
            imagefilledrectangle($image_p, 0, 0, $imgWidth, $imgHeight, $white);

            $dest_x = 0;
            $dest_y = 0;

            if ($centerImage) {
                $dest_x = ceil($imgWidth/2 - $width/2);
                $dest_y = ceil($imgHeight/2 - $height/2);
            }

            imagecopyresampled($image_p, $image, $dest_x, $dest_y, $offset_x, $offset_y, $width, $height, $width_orig, $height_orig);
            
            if ($returnJpeg) {
                imagejpeg($image_p, $destPath, Phpr::$config->get('IMAGE_JPEG_QUALITY', 70));
            } else {
                imagepng($image_p, $destPath, 8);
            }

            @chmod($destPath, Phpr_Files::getFilePermissions());
            
            imagedestroy($image_p);
            imagedestroy($image);

        } else {
            self::im_resample($srcPath, $destPath, $width, $height, $imgWidth, $imgHeight, $returnJpeg);
        }
    }

    private static function copter_create_image($extension, $srcPath)
    {
        switch ($extension) 
        {
            case 'jpeg' :
            case 'jpg' :
                return @imagecreatefromjpeg($srcPath);
            case 'png' : 
                return @imagecreatefrompng($srcPath);
            case 'gif' :
                return @imagecreatefromgif($srcPath);
        }
        
        return null;
    }

}
