<?php

namespace Biigle\Modules\Geo;

use Illuminate\Database\Eloquent\Model;
use Biigle\Volume;

class WebMapOverlay extends Model
{
    /**
     * Don't maintain timestamps for this model.
     *
     * @var bool
     */
    public $timestamps = false;

    protected $casts = [
        'layers' => 'array'
    ];

    /**
     * The attributes hidden in the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'volume_id',
    ];

    protected $fillable = [
        'url',
        'name',
        'layers',
        'browsing_layer',
        'context_layer'
    ];

    /**
     * The volume, this overlay belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function volume()
    {
        return $this->belongsTo(Volume::class);
    }
}
