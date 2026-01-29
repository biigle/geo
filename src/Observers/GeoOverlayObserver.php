<?php

namespace Biigle\Modules\Geo\Observers;

use Biigle\Modules\Geo\GeoOverlay;

class GeoOverlayObserver
{
    /**
     * Delete overlay tiles if overlay is geotiff
     *
     * @param GeoOverlay $overlay
     *
     * @return void
     */
    public function deleted(GeoOverlay $overlay)
    {
        if ($overlay->type === 'geotiff') {
            $overlay->deleteFile();
        }
    }
}
