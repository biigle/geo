<?php

namespace Biigle\Modules\Geo\Http\Controllers\Api;

use Biigle\Modules\Geo\WebMapOverlay;
use Illuminate\Http\Request;
use Biigle\Http\Controllers\Api\Controller;
use Biigle\Modules\Geo\Http\Requests\StoreWebMapOverlay;

class WebMapOverlayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWebMapOverlay $request)
    {
        $baseUrl = $request->input('url');
    }

    /**
     * Display the specified resource.
     */
    public function show(WebMapOverlay $webMapOverlay)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WebMapOverlay $webMapOverlay)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WebMapOverlay $webMapOverlay)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WebMapOverlay $webMapOverlay)
    {
        //
    }
}
