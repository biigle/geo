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

    $features = collect($images)->map(function($image){
      $feature = new \GeoJson\Feature\Feature(new \GeoJson\Geometry\Point([$image->lng, $image->lat]));
      return $feature;
    });

    $geojson_feature_Collection = new \GeoJson\Feature\FeatureCollection($features->all());
    return $geojson_feature_Collection;
  }
}
