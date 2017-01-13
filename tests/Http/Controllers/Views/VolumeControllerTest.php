<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Views;

use ApiTestCase;
use Biigle\Tests\ImageTest;

class VolumeControllerTest extends ApiTestCase
{
    public function testShow() {
        $id = $this->volume()->id;

        // not logged in
        $this->get("volumes/{$id}/geo");
        $this->assertResponseStatus(302);

        // doesn't belong to project
        $this->beUser();
        $this->get("volumes/{$id}/geo");
        $this->assertResponseStatus(403);

        $this->beEditor();
        $this->get("volumes/{$id}/geo");
        $this->assertResponseStatus(404);

        ImageTest::create([
            'lng' => 5.5,
            'lat' => 5.5,
            'volume_id' => $this->volume()->id,
        ]);
        $this->volume()->flushGeoInfoCache();

        $this->get("volumes/{$id}/geo");
        $this->assertResponseOk();
    }
}
