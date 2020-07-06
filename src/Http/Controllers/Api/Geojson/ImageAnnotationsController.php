<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Image;
use Biigle\Modules\Geo\Src\Libraries\LabelCoordinates;
use GeoJson\Feature\FeatureCollection;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ImageAnnotationsController extends Controller{

  public function index($id)
  {
    $image = Image::findOrFail($id);
    $this->authorize('access', $image);

    $labels = Image::Join('annotations', 'annotations.image_id', '=', 'images.id')
                    ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
                    ->join('labels', 'labels.id', '=', 'annotation_labels.label_id')
                    ->where("image_id", $id);
    $labelCoordinates = new LabelCoordinates($labels->get());
    $results = $labelCoordinates->compute();
    return new FeatureCollection($results->all());
  }
}
?>
