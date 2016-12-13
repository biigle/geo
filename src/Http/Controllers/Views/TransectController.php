<?php

namespace Dias\Modules\Geo\Http\Controllers\Views;

use DB;
use Dias\Role;
use Dias\Transect;
use Dias\LabelTree;
use Illuminate\Contracts\Auth\Guard;
use Dias\Http\Controllers\Views\Controller;

class TransectController extends Controller
{
    /**
     * Shows the transect geo page.
     *
     * @param Guard $auth
     * @param int $id transect ID
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Guard $auth, $id)
    {
        $transect = Transect::select('id', 'name')->findOrFail($id);
        $this->authorize('access', $transect);
        if (!$transect->hasGeoInfo()) abort(404);

        $user = $auth->user();

        $images = $transect->images()->select('id', 'lng', 'lat')->get();

        if ($user->isAdmin) {
            // admins have no restrictions
            $projectIds = $transect->projects()->pluck('id');
        } else {
            // array of all project IDs that the user and the transect have in common
            // and where the user is editor or admin
            $projectIds = DB::table('project_user')
                ->where('user_id', $user->id)
                ->whereIn('project_id', function ($query) use ($transect) {
                    $query->select('project_transect.project_id')
                        ->from('project_transect')
                        ->join('project_user', 'project_transect.project_id', '=', 'project_user.project_id')
                        ->where('project_transect.transect_id', $transect->id)
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

        return view('geo::show', compact('transect', 'images', 'trees'));
    }
}
