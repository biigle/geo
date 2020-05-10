<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Volume;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class VolumeImagesController extends Controller
{
  public function index($id){
    $volume = Volume::findOrFail($id);
    $this->authorize('access', $volume);
    $images = $volume->images;
    return $images[0];
  }
}
