<?php

namespace Biigle\Modules\Geo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Biigle\Volume;
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
        'context_layer'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'top_left_lng' => 'float',
        'top_left_lat' => 'float',
        'bottom_right_lng' => 'float',
        'bottom_right_lat' => 'float',
    ];

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
        return "{$this->id}/{$this->id}_original";
    }

    /**
     * Store the uploaded image file of the geo overlay.
     *
     * @param UploadedFile $file
     */
    public function storeFile(UploadedFile $file)
    {
        Storage::disk(config('geo.tiles.overlay_storage_disk'))
            ->putFileAs($this->id, $file, "{$this->id}_original");
    }
}
