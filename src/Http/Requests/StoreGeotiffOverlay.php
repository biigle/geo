<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
use Illuminate\Support\Str;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\Geo\Services\Support\GeoManager;

class StoreGeotiffOverlay extends FormRequest
{
    /**
     * The volume for which the new overlay should be stored.
     *
     * @var Volume
     */
    public $volume;

    /**
     * The geoManager used to process geo data
     * 
     * @var GeoManager
     */
    public $geotiff;

    public function __construct(GeoManager $geotiff)
    {
        $this->geotiff = $geotiff;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->volume = Volume::findOrFail($this->route('id'));

        return $this->user()->can('update', $this->volume);
    }

    /**
     * Get the validation rules that apply to the request.
     * make sure th file is in tiff format and not bigger than 50GB (52.430.000 kilobytes)
     *
     * @return array
     */
    public function rules()
    {
        $overlayCount = GeoOverlay::where('volume_id', $this->volume->id)->count();
        return [
            'geotiff' => 'required|file|mimetypes:image/tiff',
            'layer_index' => "required|integer|between:0,$overlayCount",
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $validator->after(function ($validator) {

            if ($this->volume->isVideoVolume()) {
                throw ValidationException::withMessages(["id" => "Geo overlays are not available for video volumes."]);
            }

            $file = $this->file('geotiff');

            $fileName = $file->getClientOriginalName();
            $overlayExists = GeoOverlay::where('volume_id', $this->volume->id)
                ->where('type', 'geotiff')
                ->where('name', $fileName)
                ->exists();

            if ($overlayExists) {
                $fileNameShort = Str::limit($fileName, 25);
                throw ValidationException::withMessages(["fileExists" => "The geoTIFF \"{$fileNameShort}\" has already been uploaded."]);
            }

            $this->geotiff->useFile($file);

            $colorCount = $this->geotiff->getKey('IFD0:SamplesPerPixel');
            if (!is_null($colorCount) && $colorCount > 4) {
                $validator->errors()->add('invalidColorSpace', "Invalid color space. The image can have at most 4 color channels, but $colorCount channels are given.");
            }

            $modelType = $this->geotiff->getCoordSystemType();
            $epsg = $this->geotiff->getEpsgCode();

            if (is_null($modelType)) {
                throw ValidationException::withMessages(["MissingModelType" => "The geoTIFF file does not have the required GTModelTypeTag."]);
            }

            if ($modelType != 'projected' && $epsg != 4326) {
                $validator->errors()->add('wrongModelType', "The coordinate reference system (CRS) of type '{$modelType}' is not supported. Please use a 'projected' CRS or EPSG:4326 instead.");
            }

            if (is_null($epsg)) {
                $validator->errors()->add('noPCSKEY', "Did not detect the 'ProjectedCSType' or 'GeographicType' geokey in the geoTIFF metadata.");
            } elseif ($epsg === 0) {
                $validator->errors()->add('unDefined', "The coordinate reference system (CRS) is undefined. Please use a 'projected' CRS or EPSG:4326 instead.");
            } elseif ($epsg === 32767) {
                $validator->errors()->add('userDefined', "User-defined coordinate reference systems (CRS) are not supported. Please use a 'projected' CRS or EPSG:4326 instead.");
            }
        });
    }

}
