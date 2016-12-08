<?php

namespace Dias\Modules\Geo\Http\Controllers\Views;

use Dias\Transect;
use Dias\Http\Controllers\Views\Controller;

class TransectController extends Controller
{
    /**
     * Shows the transect geo page.
     *
     * @param int $id transect ID
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $transect = Transect::findOrFail($id);
        $this->authorize('access', $transect);
        if (!$transect->hasGeoInfo()) abort(404);

        $images = $transect->images()->select('id', 'lng', 'lat')->get();

        return view('geo::show', compact('transect', 'images'));
    }
}
