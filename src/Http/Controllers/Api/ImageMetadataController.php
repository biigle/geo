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
     * ['id': 2, 
     * 'filename': 'image1', 
     * 'volume_id': 2, 
     * 'uuid': bc4f17bc-459f-4a68-9003A-35addec7354f, 
     * 'taken_at': 2024-08-13 21:44:12, 
     * 'lng': 414919.629674, 
     * 'lat': 8032131.178341,
     * 'attrs': {'metadata': {'gps_altitude': -3429.406, 'yaw': 129.130866}, 
     *           'width': 2800,
     *           'height': 4320
     *          }, 
     * 'tiled': f
     * ]
     *
     * @param  int  $volumeId
     * @param  int  $imageId
     * @return Biigle\Image
     */
    public function getImageMetadata($volumeId, $imageId)
    {
        $image = Image::where('volume_id', $volumeId)->where('id', $imageId)->first();
        return $image;
    }
}