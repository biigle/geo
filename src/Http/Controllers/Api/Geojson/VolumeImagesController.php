<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Volume;
use Biigle\Image;
use GeoJson\Feature\{Feature, FeatureCollection};
use GeoJson\Geometry\Point;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class VolumeImagesController extends Controller
{
  public function index($id)
  {
    $volume = Volume::findOrFail($id);
    $this->authorize('access', $volume);

    $images = $volume->images()->select('id', 'lat', 'lng');
    $labels = $images->join('annotations', 'annotations.image_id', '=', 'images.id')
      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id')
      ->select('images.id', 'images.lat', 'images.lng','labels.name')->get()
      ->groupBy('id')->map(function($item, $key){
        return $item->groupBy('name')->map(function($v){
          return $v->count();
        });
      });

    $features = $images->get()->map(function($image) use($labels){
      $feature = new Feature(new Point([$image->lng, $image->lat]), $labels[$image->id]->all());
      return $feature;
    });

    return new FeatureCollection($features->all());;
  }
}
