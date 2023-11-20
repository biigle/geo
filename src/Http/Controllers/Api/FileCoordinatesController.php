<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Volume;

class FileCoordinatesController extends Controller
{
    /**
     * Get all files of a volume together with coordinate-values.
     *
     * @api {get} volumes/:id/file-coordinates Get files with coordinates
     * @apiGroup Volumes
     * @apiName VolumeIndexFilenames
     * @apiPermission projectMember
     * @apiDescription Returns IDs of files with lng and lat coordinates.
     *
     * @apiParam {Number} id The volume ID
     * 
     * TODO: add example
     * @apiSuccessExample {json} Success response:
     * {
     *    "123": {},
     *    "321": "other-image.jpg"
     * }
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $volume = Volume::findOrFail($id);
        $this->authorize('access', $volume);

        return $volume->images()->select('id', 'lng', 'lat')->get();
    }
}
