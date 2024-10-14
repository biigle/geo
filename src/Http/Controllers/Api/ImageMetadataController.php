<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Image;

class ImageMetadataController extends Controller
{
    /**
     * Get the image metadata.
     *
     * @api {get} volumes/{id}/images/metadata/{image_id} Get images with metadata
     * @apiGroup Images
     * @apiName getImageMetadata
     * @apiPermission projectMember
     * @apiDescription Returns metadata of images.
     *
     * @apiParam {Number} volumeId
     * @apiParam {Number} imageId
     *
     * @apiSuccessExample {json} Success response:
     * ['gps_altitude': 589.05152, 'yaw': 129.130866]
     *
     * @param  int  $volumeId
     * @param  int  $imageId
     * @return \Illuminate\Http\Response
     */
    public function getImageMetadata($volumeId, $imageId)
    {
        return Image::where('volume_id', $volumeId)->where('id', $imageId)->first()->metadata;
    }
}