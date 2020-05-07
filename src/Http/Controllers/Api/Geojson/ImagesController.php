<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use Biigle\Image;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ImagesController extends Controller
{
  public function index(){
    $images = Image::all();
    return $images;
  }
}
