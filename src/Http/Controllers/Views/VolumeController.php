<?php

namespace Biigle\Modules\Geo\Http\Controllers\Views;

use Biigle\Volume;
use Biigle\Project;
use Biigle\LabelTree;
use Illuminate\Http\Request;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Http\Controllers\Views\Controller;

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
        if (!$volume->hasGeoInfo()) {
            abort(404);
        }

        $user = $request->user();

        $images = $volume->images()->select('id', 'lng', 'lat')->get();

        if ($user->can('sudo')) {
            // Global admins have no restrictions.
            $projectIds = $volume->projects()->pluck('id');
        } else {
            // Array of all project IDs that the user and the volume have in common
            // and where the user is editor, expert or admin.
            $projectIds = Project::inCommon($user, $volume->id)->pluck('id');
        }

        // all label trees that are used by all projects which are visible to the user
        $trees = LabelTree::with('labels')
            ->select('id', 'name')
            ->whereIn('id', function ($query) use ($projectIds) {
                $query->select('label_tree_id')
                    ->from('label_tree_project')
                    ->whereIn('project_id', $projectIds);
            })
            ->get();

        $overlays = GeoOverlay::where('volume_id', $id)->get();

        return view('geo::volumes.show', compact(
            'volume',
            'images',
            'trees',
            'overlays'
        ));
    }
}
