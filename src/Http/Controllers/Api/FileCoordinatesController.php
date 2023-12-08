<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\Volume;

class FileCoordinatesController extends Controller
{
    /**
     * Get all files of a volume together with coordinate-values.
     *
     * @api {get} volumes/:id/coordinates Get files with coordinates
     * @apiGroup Volumes
     * @apiName VolumeIndexCoordinates
     * @apiPermission projectMember
     * @apiDescription Returns IDs of files with lng and lat coordinates.
     *
     * @apiParam {Number} id The volume ID
     * 
     * @apiSuccessExample {json} Success response:
     * {
     *    "0": {
     *           'id': 1, 
     *           'lat': -7.0772818384026,
     *           'lng': -88.464813199987
     *         },
     *    "1": {
     *           'id': 2,
     *           'lat': -7.0777,
     *           'lng': -66.666
     *          }
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
