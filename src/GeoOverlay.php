<?php

namespace Biigle\Modules\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Biigle\Volume;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Storage;

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
        'context_layer',
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
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Delete the overlay image file when the model is deleted.
        static::deleting(function ($overlay) {
            Storage::disk(config('geo.tiles.overlay_storage_disk'))->deleteDirectory($overlay->id);
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
}
