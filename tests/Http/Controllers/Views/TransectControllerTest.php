<?php

namespace Dias\Tests\Modules\Geo\Http\Controllers\Views;

use ApiTestCase;
use Dias\Tests\ImageTest;

class TransectControllerTest extends ApiTestCase
{
    public function testShow() {
        $id = $this->transect()->id;

        // not logged in
        $this->get("transects/{$id}/geo");
        $this->assertResponseStatus(302);

        // doesn't belong to project
        $this->beUser();
        $this->get("transects/{$id}/geo");
        $this->assertResponseStatus(403);

        $this->beEditor();
        $this->get("transects/{$id}/geo");
        $this->assertResponseStatus(404);

        ImageTest::create([
            'lng' => 5.5,
            'lat' => 5.5,
            'transect_id' => $this->transect()->id,
        ]);
        $this->transect()->flushGeoInfoCache();

        $this->get("transects/{$id}/geo");
        $this->assertResponseOk();
    }
}
