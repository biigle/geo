<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\Exceptions\ConvertModelSpaceException;
use Exception;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\Geo\Http\Requests\StoreGeotiffOverlay;
use Biigle\Modules\Geo\Exceptions\TransformCoordsException;

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
     * @apiParam {Number} volumeId The volume ID.
     * @apiParam (Required attributes) {File} geotiff The geotiff file of the geo overlay. Allowed file formats are TIFF. The file must not be larger than 50 GByte.
     * 
     * @apiParamExample {String} Request example:
     * volumeId: 1
     * geotiff: bath_map_1.tif
     *
     * @apiSuccessExample {json} Success response:
     * {
     *   "volume_id" => 1
     *   "name" => "standardEPSG2013.tif"
     *   "top_left_lng" => "-2.9198048485707"
     *   "top_left_lat" => "57.0974838573358"
     *   "bottom_right_lng" => "-2.9170062535908"
     *   "bottom_right_lat" => "57.0984589659731"
     *   "id" => 3
     *   "browsing_layer" => false
     *   "attrs" => {"width": 648, "height": 480}
     * }
     *
     * @param StoreGeotiffOverlay $request
     */
    public function storeGeoTiff(StoreGeotiffOverlay $request)
    {

        $file = $request->file('geotiff');
        $fileName = $request->input('name', $file->getClientOriginalName());
        // create GeoManager-class from uploadedFile
        $geotiff = $request->geotiff;
        $volumeId = $request->volume->id;

        $pixelDimensions = $geotiff->getPixelSize();
        $corners = $geotiff->getCorners();
        $epsg = $geotiff->getEpsgCode();

        try {
            // Convert corners from RASTER-SPACE to MODEL-SPACE
            $coords = $geotiff->convertToModelSpace($corners);

            if ($epsg != 4326) {
                $coords = $geotiff->transformModelSpace($coords, "EPSG:{$epsg}");
            }

            // Handle coordinates at wrap point
            // Transform edges:
            //   _               _
            //    | + |_ to |_ +  |
            if ($coords[0] > 0 && $coords[2] < 0) {
                $coords[2] += 360;
            }

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
