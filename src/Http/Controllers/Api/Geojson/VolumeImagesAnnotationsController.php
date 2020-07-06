<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Volume;
use Biigle\Image;
use Biigle\Modules\Geo\Src\Libraries\LabelCoordinates;
use Biigle\Http\Controllers\Api\Controller;
use GeoJson\Feature\FeatureCollection;
use League\Flysystem\FileNotFoundException;

class VolumeImagesAnnotationsController extends Controller
{
  public function index($id)
  {
    $volume = Volume::findOrFail($id);
    $this->authorize('access', $volume);

    $labels = $volume->images()->join('annotations', 'annotations.image_id', '=', 'images.id')
                      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
                      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id');
    $labelCoordinates = new LabelCoordinates($labels->get());
    $results = $labelCoordinates->compute();
    return new FeatureCollection($results->all());
  }
}
