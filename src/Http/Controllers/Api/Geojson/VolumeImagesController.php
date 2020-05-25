<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Volume;
use Biigle\Image;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class VolumeImagesController extends Controller
{
  public function index($id){
    $volume = Volume::findOrFail($id);
    $this->authorize('access', $volume);

    $images = Image::where('volume_id', $id)->with(['annotations'=>function($a){
      $a->with(['labels'=>function($al){
        $al->with(['label'=>function($l){
          $l->select('name','id');
        }])->select('id', 'annotation_id', 'label_id');
      }])->select('id', 'image_id');
    }])->select('id', 'lat', 'lng')->get()->sortBy('id');

    $features = $images->map(function($image){
      $labels = $image->annotations->pluck('labels')->flatten()->pluck('label');
      $feature = new \GeoJson\Feature\Feature(new \GeoJson\Geometry\Point([$image->lng, $image->lat]), Array('labels'=>$labels));
      return $feature;
    });

    $geojson_feature_Collection = new \GeoJson\Feature\FeatureCollection($features->all());
    return $geojson_feature_Collection;
  }
}
