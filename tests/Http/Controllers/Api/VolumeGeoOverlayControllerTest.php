<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;

class VolumeGeoOverlayControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->volume_id;

        $this->doTestApiRoute('GET', "/api/v1/volumes/{$id}/geo-overlays");

        $this->beUser();
        $this->get("/api/v1/volumes/{$id}/geo-overlays");
        $this->assertResponseStatus(403);

        $this->beGuest();
        $this->json('GET', "/api/v1/volumes/{$id}/geo-overlays")
            ->seeJson([$overlay->toArray()]);
        $this->assertResponseOk();
    }
}
