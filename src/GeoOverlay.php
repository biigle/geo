<?php

namespace Biigle\Modules\Geo;

use Storage;
use Biigle\Volume;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Biigle\Modules\Geo\Database\factories\GeoOverlayFactory;

class GeoOverlay extends Model
{
    /**
     * Don't maintain timestamps for this model.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes hidden in the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'volume_id',
    ];

    protected $fillable = [
        'browsing_layer',
        'layer_index',
        'type',
        'attrs->url',
        'attrs->layers',
        'attrs->top_left_lng',
        'attrs->top_left_lat',
        'attrs->bottom_right_lng',
        'attrs->bottom_right_lat'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'attrs' => 'array',
        'attrs->layers' => 'array'
    ];


    /**
     * Getter for the attrs json column
     */
    protected function attrs(): Attribute {
        
        return Attribute::make(
            get: function (mixed $value): array {
            $value = json_decode($value, true);
            $float_array = ['top_left_lng', 'top_left_lat', 'bottom_right_lng', 'bottom_right_lat'];
            
            foreach($value as $entry) {
                if (in_array($entry, $float_array)) {
                    $value[$entry] = floatval($value[$entry]);
                }
            }

            return $value;
        });
    }
    
    /**
     * The volume, this overlay belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function volume()
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * Get the local path to the overlay image file.
     *
     * @return string
     */
    public function getPathAttribute()
    {
        return "{$this->id}/{$this->id}_original.tif";
    }

    /**
     * Store the uploaded image file of the geo overlay.
     *
     * @param UploadedFile $file
     */
    public function storeFile(UploadedFile $file)
    {
        Storage::disk(config('geo.tiles.overlay_storage_disk'))
            ->putFileAs($this->id, $file, "{$this->id}_original.tif");
    }

    /**
     * Delete the uploaded image file of the geo overlay.
     *
     */
    public function deleteFile()
    {
        $exists = Storage::disk(config('geo.tiles.overlay_storage_disk'))
            ->exists($this->id);

        if ($exists) {
            Storage::disk(config('geo.tiles.overlay_storage_disk'))
                ->deleteDirectory($this->id);
        }
    }

    /**
     * Returns model's factory
     * 
     * @return GeoOverlayFactory
     */
    protected static function factory($isWebMap = false)
    {
        if ($isWebMap) {
            return GeoOverlayFactory::new()->webMap();
        }

        return GeoOverlayFactory::new();
    }

    /**
     * Builds object by using the given data and saves it.
     *
     * @param mixed $id Id of the used volume
     * @param mixed $name File name
     * @param string $type GeoOverlay type (geotiff or webmap)
     * @param mixed $attrs Array containing the corresponding attributes
     * @return GeoOverlay
     */
    public static function build($id, $name, $type, $attrs)
    {
        $overlay = new static;
        $overlay->volume_id = $id;
        $overlay->type = $type;
        $overlay->name = $name;
        $overlay->layer_index = null;
        if ($type === 'geotiff') {
            $precision = 13;
            [$coords, $pixelDimensions] = $attrs;
            $overlay->attrs = [
                "top_left_lng" => round($coords[0], $precision),
                "top_left_lat" => round($coords[1], $precision),
                "bottom_right_lng" => round($coords[2], $precision),
                "bottom_right_lat" => round($coords[3], $precision),
                "width" => $pixelDimensions[0],
                "height" => $pixelDimensions[1]
            ];
        } else {
            [$url, $layers] = $attrs;
            $overlay->attrs = [
                'url' => $url,
                'layers' => $layers,
            ];
        }
        $overlay->save();
        return $overlay;
    }
}
