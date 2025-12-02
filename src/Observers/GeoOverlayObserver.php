<?php

namespace Biigle\Modules\Geo\Observers;

use Biigle\Modules\Geo\GeoOverlay;

class GeoOverlayObserver
{
    public function deleted(GeoOverlay $overlay)
    {
        $overlay->deleteFile();
    }
}
