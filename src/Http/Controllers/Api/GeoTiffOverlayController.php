<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Exception;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Jobs\TileSingleOverlay;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\Geo\Services\Support\GeoManager;
use Biigle\Modules\Geo\Http\Requests\StoreGeotiffOverlay;

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
     *   "context_layer" => false
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

        // check whether file exists alread in DB 
        $overlayExists = GeoOverlay::where('volume_id', $volumeId)->where('name', $fileName)->exists();
        if ($overlayExists) {
            // strip the name if too long
            $fileNameShort = strlen($fileName) > 25 ? substr($fileName, 0, 25) . "..." : $fileName;
            throw ValidationException::withMessages(['fileExists' => "The geoTIFF \"{$fileNameShort}\" has already been uploaded."]);
        }

        $pixelDimensions = $geotiff->getPixelSize();
        $corners = $geotiff->getCorners();
        $pcsCode = intval($geotiff->getKey('GeoTiff:ProjectedCSType'));
        // Convert corners from RASTER-SPACE to MODEL-SPACE
        $minMaxCoords = $geotiff->convertToModelSpace($corners);

        if ($pcsCode === 4326) {
            // save data in GeoOverlay DB when already in WGS84
            $overlay = $this->saveGeoOverlay($volumeId, $fileName, $minMaxCoords, $file, $pixelDimensions);
        } else {
            // transform to WGS 84
            try {
                $minMaxCoordsWGS = $geotiff->transformModelSpace($minMaxCoords, "EPSG:{$pcsCode}");
            } catch (Exception $e) {
                throw ValidationException::withMessages(
                    [
                        'failedTransformation' => ["Could not transform CRS. Please convert $pcsCode to EPSG:4326 (WGS84) before uploading."]
                    ]
                );
            }
            $overlay = $this->saveGeoOverlay($volumeId, $fileName, $minMaxCoordsWGS, $file, $pixelDimensions);
        }

        return $overlay;
    }

    /**
     * Save GeoTIFF data in GeoOverlay DB
     *
     * @param $volumeId ID of the current volume
     * @param $fileName of the original input-file
     * @param $coords min and max coordinates in WGS84 format
     * @param $file the geotiff file from request
     *
     * @return GeoOverlay
     */
    protected function saveGeoOverlay($volumeId, $fileName, $coords, $file, $pixelDimensions)
    {
        $overlay = new GeoOverlay;
        $overlay->volume_id = $volumeId;
        $overlay->name = $fileName;
        $overlay->browsing_layer = false;
        $overlay->context_layer = false;
        $overlay->type = 'geotiff';
        $overlay->layer_index = null;
        $overlay->attrs = [
            "top_left_lng" => round($coords[0], 13),
            "top_left_lat" => round($coords[1], 13),
            "bottom_right_lng" => round($coords[2], 13),
            "bottom_right_lat" => round($coords[3], 13),
            "width" => $pixelDimensions[0],  
            "height" => $pixelDimensions[1]
        ];
        $overlay->save();
        $overlay->storeFile($file);
        $this->submitTileJob($overlay);
        return $overlay;
    }

    /**
     * Submit a new tile job for any new overlay.
     *
     * @param GeoOverlay $overlay
     */
    protected function submitTileJob(GeoOverlay $overlay)
    {
        $targetPath =  "{$overlay->id}/{$overlay->id}_tiles";
        TileSingleOverlay::dispatch($overlay, config('geo.tiles.overlay_storage_disk'), $targetPath);
    }
}
