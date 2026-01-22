<?php

return [

    'tiles' => [
        /*
         | Directory to temporarily store the tiles when they are generated.
         */
        'tmp_dir' => sys_get_temp_dir(),

        /*
        | Storage disk where the geo overlay images will be stored.
        */
        'overlay_storage_disk' => env('GEO_OVERLAY_STORAGE_DISK', 'geo-overlays'),
    ]

];