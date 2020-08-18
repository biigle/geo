<?php

namespace Biigle\Modules\Geo\Http\Controllers\Views;

use Biigle\Http\Controllers\Views\Controller;
use Biigle\Image;
use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Project;

class ProjectController extends Controller
{
    /**
     * Shows the project geo page.
     *
     * @param int $id project ID
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Project::select('id', 'name')->findOrFail($id);
        $this->authorize('access', $project);
        if (!$project->hasGeoInfo()) {
            abort(404);
        }

        $volumeIdQuery = function ($query) use ($id) {
            return $query->select('volume_id')
                ->from('project_volume')
                ->where('project_id', $id);
        };

        $images = Image::whereIn('volume_id', $volumeIdQuery)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->select('id', 'lat', 'lng')
            ->get();

        $trees = $project->labelTrees()->with('labels', 'version')->get();

        unset($project->labelTrees);

        $overlays = GeoOverlay::whereIn('volume_id', $volumeIdQuery)->get();

        return view('geo::projects.show', compact(
            'project',
            'images',
            'trees',
            'overlays'
        ));
    }
}
