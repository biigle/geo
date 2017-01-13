<?php

namespace Biigle\Modules\Geo\Http\Controllers\Views;

use DB;
use Biigle\Role;
use Biigle\Volume;
use Biigle\LabelTree;
use Illuminate\Contracts\Auth\Guard;
use Biigle\Http\Controllers\Views\Controller;

class VolumeController extends Controller
{
    /**
     * Shows the volume geo page.
     *
     * @param Guard $auth
     * @param int $id volume ID
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Guard $auth, $id)
    {
        $volume = Volume::select('id', 'name')->findOrFail($id);
        $this->authorize('access', $volume);
        if (!$volume->hasGeoInfo()) abort(404);

        $user = $auth->user();

        $images = $volume->images()->select('id', 'lng', 'lat')->get();

        if ($user->isAdmin) {
            // admins have no restrictions
            $projectIds = $volume->projects()->pluck('id');
        } else {
            // array of all project IDs that the user and the volume have in common
            // and where the user is editor or admin
            $projectIds = DB::table('project_user')
                ->where('user_id', $user->id)
                ->whereIn('project_id', function ($query) use ($volume) {
                    $query->select('project_volume.project_id')
                        ->from('project_volume')
                        ->join('project_user', 'project_volume.project_id', '=', 'project_user.project_id')
                        ->where('project_volume.volume_id', $volume->id)
                        ->whereIn('project_user.project_role_id', [Role::$editor->id, Role::$admin->id]);
                })
                ->pluck('project_id');
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

        return view('geo::show', compact('volume', 'images', 'trees'));
    }
}
