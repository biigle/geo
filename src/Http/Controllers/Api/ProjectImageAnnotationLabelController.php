<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Http\Controllers\Api\Controller;
use Biigle\ImageAnnotation;
use Biigle\Project;
use Illuminate\Http\Request;

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
     * @param Request $request
     * @param  int  $pid
     * @param  int  $lid
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $pid, $lid)
    {
        $project = Project::findOrFail($pid);
        $this->authorize('access', $project);

        $user = $request->user();
        // Respect the active annotation sessions of the volumes (like the volume
        // counterpart of this endpoint).
        $sessions = $project->getActiveAnnotationSessions($user);

        return ImageAnnotation::join('image_annotation_labels', 'image_annotations.id', '=', 'image_annotation_labels.annotation_id')
                ->join('images', 'image_annotations.image_id', '=', 'images.id')
                ->where('image_annotation_labels.label_id', $lid)
                ->whereIn('images.volume_id', function ($query) use ($pid) {
                    return $query->select('volume_id')
                        ->from('project_volume')
                        ->where('project_id', $pid);
                })
                ->allowedBySessions($sessions, $user)
                ->groupBy('images.id')
                ->pluck('images.id');
    }
}
