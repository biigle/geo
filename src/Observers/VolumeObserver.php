<?php

namespace Biigle\Modules\Geo\Observers;

use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Volume;

class VolumeObserver
{

    /**
     * Delete geo overlay files when volume is deleted
     * 
     * @param \Biigle\Volume $volume
     * 
     * @return void
     */
    public function deleting(Volume $volume)
    {
        $overlay = GeoOverlay::where('volume_id', '=', $volume->id)->first();

        if ($overlay) {
            $overlay->deleteFile();
        }
    }
}
