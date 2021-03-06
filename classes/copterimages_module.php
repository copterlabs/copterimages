<?php

class CopterImages_Module extends Core_ModuleBase
{

    /**
     * Creates the module information object
     * @return Core_ModuleInfo
     */
    protected function createModuleInfo(  )
    {
        return new Core_ModuleInfo(
            "Force Image Cropping",
            "Adds the option to force crop images to a given size.",
            "Copter Labs Inc."
        );
    }

    /**
     * Subscribes to the core:onProcessImage event
     * @return void
     */
    public function subscribeEvents(  )
    {
        Backend::$events->addEvent('core:onProcessImage', $this, 'force_crop_image');
    }

    /**
     * Sets up file paths, checks for an existing image, fires up the helper
     * @return string   The new image path
     */
    public function force_crop_image($file, $width, $height, $return_jpeg, $params)
    {
        if ($params['mode'] != 'force_crop') {
            return;
        }

        $ext = $return_jpeg ? 'jpg' : 'png';

        $image_path = '/uploaded/thumbnails/' . $file->id 
            . '_' . filemtime(PATH_APP.$file->getPath()) 
            . '_' . $width . 'x' . $height . '.' . $ext;
        $image_file = PATH_APP . $image_path;

        if (file_exists($image_file)) {
            return $image_path;
        }

        try {
            CopterImages_Helper::makeThumbnail($file->getFileSavePath($file->disk_name), $image_file, $width, $height, false, $params['mode'], $return_jpeg);
        } catch (Exception $ex) {
            @copy(PATH_APP . '/phproad/resources/images/thumbnail_error.gif', $image_file);
        }

        return $image_path;
    }

}
