<?php

namespace Biigle\Modules\Geo\Observers;

use Biigle\Volume;
use Biigle\Modules\Geo\GeoOverlay;

class VolumeObserver
{

    /**
     * Delete geo overlay files when volume is deleted
     * 
     * @param \Biigle\Volume $volume
     * 
     */
    public function deleting(Volume $volume)
    {
        GeoOverlay::where('volume_id', '=', $volume->id)
            ->where('type', '=', 'geotiff')
            ->each(fn($overlay) => $overlay->deleteFile());
    }
}
