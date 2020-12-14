<?php

namespace Biigle\Modules\Geo\Http\Controllers\Views;

use Biigle\Http\Controllers\Views\Controller;
use Biigle\LabelTree;
use Biigle\Project;
use Biigle\Volume;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VolumeController extends Controller
{
    /**
     * Shows the volume geo page.
     *
     * @param Request $request
     * @param int $id volume ID
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $volume = Volume::select('id', 'name')->findOrFail($id);
        $this->authorize('access', $volume);
        if ($volume->isVideoVolume() || !$volume->hasGeoInfo()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        $images = $volume->images()->select('id', 'lng', 'lat')->get();

        if ($user->can('sudo')) {
            // Global admins have no restrictions.
            $projectIds = $volume->projects()->pluck('id');
        } else {
            // Array of all project IDs that the user and the volume have in common.
            $projectIds = Project::inCommon($user, $volume->id)->pluck('id');
        }

        // All label trees that are used by all projects which are visible to the user.
        $trees = LabelTree::select('id', 'name', 'version_id')
            ->with('labels', 'version')
            ->whereIn('id', function ($query) use ($projectIds) {
                $query->select('label_tree_id')
                    ->from('label_tree_project')
                    ->whereIn('project_id', $projectIds);
            })
            ->get();

        return view('geo::volumes.show', compact(
            'volume',
            'images',
            'trees'
        ));
    }
}
