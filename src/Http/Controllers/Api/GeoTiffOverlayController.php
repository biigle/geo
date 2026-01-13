<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Exception;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\Geo\Services\Support\Transformer;
use Biigle\Modules\Geo\Http\Requests\StoreGeotiffOverlay;
use Biigle\Modules\Geo\Exceptions\TransformCoordsException;
use Biigle\Modules\Geo\Exceptions\ConvertModelSpaceException;

class GeoTiffOverlayController extends Controller
{
    /**
     * Stores a new geo overlay that was uploaded with the geotiff method.
     *
     * @api {post} volumes/:id/geo-overlays/geotiff Upload a geotiff geo overlay
     * @apiGroup Geo
     * @apiName VolumesStoreGeoTiff
     * @apiPermission projectAdmin
     *
     * @apiParam (Required attributes) {File} geotiff The geotiff file of the geo overlay. Allowed file formats are TIFF. The file must not be larger than 50 GByte.
     * 
     * @apiParamExample {String} Request example:
     * geotiff: bath_map_1.tif
     *
     * @apiSuccessExample {json} Success response:
     * {
     * "id" => 1
     * "name" => "standardEPSG2013.tif"
     * "type" => "geotiff"
     * "browsing_layer" => true
     * "layer_index" => null
     * "attrs" => [
     *  "top_left_lng" => -2.9213164328107
     *  "top_left_lat" => 57.096651484989
     *  "bottom_right_lng" => -2.9185182540292
     *  "bottom_right_lat" => 57.097626526122
     *  "width" => 3
     *  "height" => 2
     *  ]
     * }
     *
     * @param StoreGeotiffOverlay $request
     *
     * @return GeoOverlay
     */
    public function storeGeoTiff(StoreGeotiffOverlay $request)
    {
        $file = $request->file('geotiff');
        $fileName = $request->input('name', $file->getClientOriginalName());
        // create GeoManager-class from uploadedFile
        $geotiff = $request->geotiff;
        $volumeId = $request->volume->id;

        $pixelDimensions = $geotiff->getPixelSize();
        $epsg = $geotiff->getEpsgCode();

        try {
            $coords = $geotiff->getCoords();
            $overlay = GeoOverlay::build($volumeId, $fileName, 'geotiff' , [$coords, $pixelDimensions]);
            $overlay->storeFile($file);
            TileSingleOverlay::dispatch($overlay, $request->user(),  $geotiff->exif);
            return $overlay->fresh();

        } catch (ConvertModelSpaceException | TransformCoordsException | Exception $e) {
            $msg = [];
            if ($e instanceof ConvertModelSpaceException) {
                $msg = ['affineTransformation' => 'The geoTIFF file does not have an affine transformation.'];
            } else if ($e instanceof TransformCoordsException) {
                $msg = [
                    'failedTransformation' =>
                        "Could not transform CRS. Please convert EPSG:$epsg to EPSG:4326 (WGS84) before uploading."
                ];
            } else {
                $msg = ['failedUpload' => "The file \"$fileName\" could not be uploaded. Please try again."];
            }
            throw ValidationException::withMessages($msg);
        }
    }
}
