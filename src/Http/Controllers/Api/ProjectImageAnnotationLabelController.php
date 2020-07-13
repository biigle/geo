<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Annotation;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Project;

class ProjectImageAnnotationLabelController extends Controller
{
    /**
     * List the IDs of images of a project having one or more annotations with the specified label.
     *
     * @api {get} projects/:pid/images/filter/annotation-label/:lid Get images with label
     * @apiGroup Projects
     * @apiName ProjectImagesHasLabel
     * @apiPermission projectMember
     * @apiDescription Returns IDs of images having one or more annotations with the specified label.
     *
     * @apiParam {Number} pid The project ID
     * @apiParam {Number} lid The label ID
     *
     * @apiSuccessExample {json} Success response:
     * [1, 5, 6]
     *
     * @param  int  $pid
     * @param  int  $lid
     * @return \Illuminate\Http\Response
     */
    public function index($pid, $lid)
    {
        // TODO: This endpoint does not respect annotation sessions like its volume
        // counterpart!

        $project = Project::findOrFail($pid);
        $this->authorize('access', $project);

        return Annotation::join('annotation_labels', 'annotations.id', '=', 'annotation_labels.annotation_id')
                ->join('images', 'annotations.image_id', '=', 'images.id')
                ->where('annotation_labels.label_id', $lid)
                ->whereIn('images.volume_id', function ($query) use ($pid) {
                    return $query->select('volume_id')
                        ->from('project_volume')
                        ->where('project_id', $pid);
                })
                ->groupBy('images.id')
                ->pluck('images.id');
    }
}
