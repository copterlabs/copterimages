Force Crop Images LemonStand Module
===================================

A LemonStand module that adds the ability to force crop an image to a 
given size. 


Installation
------------

1.  Download a copy of the module
2.  Upload the `copterimages` folder to the `modules` directory
3.  Verify that the module is installed by visiting the Modules directory in 
    the LemonStand dashboard


Usage
-----

To force crop an image, set the `mode` parameter to `force_crop` in calls to 
`getThumbnailPath()` or `image_url()`:

    $product->getThumbnailPath(300, 200, TRUE, array('mode'=>'force_crop'));


Known Issues
------------

This module doesn't do well with images that are smaller than the defined size.
