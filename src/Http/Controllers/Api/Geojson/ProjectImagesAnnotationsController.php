<?php
namespace Biigle\Modules\Geo\Http\Controllers\Api\Geojson;

use DB;
use Biigle\Project;
use Biigle\Image;
use Biigle\Modules\Geo\Src\Libraries\LabelCoordinates;
use GeoJson\Feature\FeatureCollection;
use Biigle\Http\Controllers\Api\Controller;
use League\Flysystem\FileNotFoundException;

class ProjectImagesAnnotationsController extends Controller{

  public function index($id)
  {
    $project = Project::findOrFail($id);
    $this->authorize('access', $project);

    $labels = Image::wherein("volume_id", $project->volumes->pluck('id')->all())
                      ->join('annotations', 'annotations.image_id', '=', 'images.id')
                      ->join('annotation_labels', 'annotation_labels.annotation_id', '=', 'annotations.id')
                      ->join('labels', 'labels.id', '=', 'annotation_labels.label_id');
    $labelCoordinates = new LabelCoordinates($labels->get());
    $results = $labelCoordinates->compute();
    return new FeatureCollection($results->all());
  }
}
?>
