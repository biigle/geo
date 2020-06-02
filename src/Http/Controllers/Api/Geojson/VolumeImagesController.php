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

    $images = $volume->images()->select('id', 'lat', 'lng', 'filename')->get();
    $labels = $volume->images()->join('annotations', 'annotations.image_id', '=', 'images.id')
      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id')
      ->select('images.id', 'images.lat', 'images.lng','labels.name')->get()
      ->groupBy('id');

    $images->each(function($item, $key) use($labels){
      $item['label_count'] = $labels->has($item->id) ? $labels[$item->id]->groupBy('name')->map(function($v){
        return $v->count();
      }) : collect([]);
    });

    $features = $images->map(function($image){
      $feature = new Feature(new Point([$image->lng, $image->lat]), array_merge(['_id'=>$image->id, '_filename'=>$image->filename], $image->label_count->all()));
      return $feature;
    });

    return new FeatureCollection($features->all());;
  }
}
