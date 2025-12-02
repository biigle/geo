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
        return [
            'geotiff' => 'required|file|mimetypes:image/tiff',
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
        $validator->after(function ($validator) {
            if (!$this->hasFile('geotiff')) {
                throw ValidationException::withMessages(['invalid' => 'Invalid request. Geotiff is missing.']);
            }

            if ($this->volume->isVideoVolume()) {
                $validator->errors()->add('id', 'Geo overlays are not available for video volumes.');
            }

            $file = $this->file('geotiff');
            $this->geotiff->useFile($file);
            $pcsCode = is_null($this->geotiff->getKey('GeoTiff:ProjectedCSType')) ?: intval($this->geotiff->getKey('GeoTiff:ProjectedCSType'));
            $modelType = $this->getCoordSystemType($this->geotiff->exif);

            if ($modelType != 'projected') {
                $validator->errors()->add('wrongModelType', "The GeoTIFF coordinate-system of type '{$modelType}' is not supported. Use a 'projected' coordinate-system instead!");
            }

            if (is_null($pcsCode)) {
                $validator->errors()->add('noPCSKEY', "Did not detect the 'ProjectedCSType' geokey in geoTIFF metadata. Make sure this key exists for geoTIFF's containing a projected coordinate system.");
            } elseif ($pcsCode === 0) {
                $validator->errors()->add('unDefined', 'The projected coordinate system (PCS) is undefined. Provide a PCS using EPSG-system instead.');
            } elseif ($pcsCode === 32767) {
                $validator->errors()->add('userDefined', 'User-defined projected coordinate systems (PCS) are not supported. Provide a PCS using EPSG-system instead.');
            }

            $fileName = $this->input('name', $file->getClientOriginalName());
            $overlayExists = GeoOverlay::where('volume_id', $this->volume->id)->where('name', $fileName)->exists();
            if ($overlayExists) {
                $fileNameShort = Str::limit($fileName, 25);
                $validator->errors()->add('fileExists', "The geoTIFF \"{$fileNameShort}\" has already been uploaded.");
            }
        });
    }

    /**
     * Retreive the type of coordinate reference system used in the geoTIFF.
     *
     * @return string the coordinate system type
     */
    public function getCoordSystemType($exif)
    {
        if (isset($exif['GeoTiff:GTModelType'])) {
            $modelTypeKey = $exif['GeoTiff:GTModelType'];
            switch ($modelTypeKey) {
                case 1:
                    $modelType = 'projected';
                    break;
                case 2:
                    $modelType = 'geographic';
                    break;
                case 3:
                    $modelType = 'geocentric';
                    break;
                case 32767:
                    $modelType = 'user-defined';
                    break;
                default:
                    $modelType = null;
            }
        } else {
            throw ValidationException::withMessages(
                [
                    'missingModelType' => ['The geoTIFF file does not have the required GTModelTypeTag.'],
                ]
            );
        }

        return $modelType;
    }
}
