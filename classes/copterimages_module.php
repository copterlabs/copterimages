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
            "Copter Labs Image Cropping",
            "Adds the option to hard-crop images to a given size.",
            "Copter Labs Inc."
        );
    }

    public function subscribeEvents(  )
    {
        Backend::$events->addEvent('core:onProcessImage', $this, 'force_crop_image');
    }

    public function force_crop_image($file, $width, $height, $return_jpeg, $params)
    {
        if ($params['mode'] != 'force_crop') {
            return;
        }

        $ext = $return_jpeg ? 'jpg' : 'png';

        $image_path = '/uploaded/thumbnails/' 
            . implode('.', array_slice(explode('.', $file->name), 0, -1)) 
            . '_' . $file->id . '_' . $width . 'x' . $height . '.' . $ext;
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
