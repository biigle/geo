<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Views;

use ApiTestCase;
use Biigle\MediaType;
use Biigle\Tests\ImageTest;

class VolumeControllerTest extends ApiTestCase
{
    public function testShow()
    {
        $id = $this->volume()->id;

        // not logged in
        $response = $this->get("volumes/{$id}/geo");
        $response->assertStatus(302);

        // doesn't belong to project
        $this->beUser();
        $response = $this->get("volumes/{$id}/geo");
        $response->assertStatus(403);

        $this->beEditor();
        $response = $this->get("volumes/{$id}/geo");
        $response->assertStatus(404);

        ImageTest::create([
            'lng' => 5.5,
            'lat' => 5.5,
            'volume_id' => $this->volume()->id,
        ]);
        $this->volume()->flushGeoInfoCache();

        $response = $this->get("volumes/{$id}/geo");
        $response->assertStatus(200);
    }

    public function testShowVideoVolume()
    {
        $id = $this->volume(['media_type_id' => MediaType::videoId()])->id;

        $this->beEditor();
        $this->get("volumes/{$id}/geo")->assertStatus(404);
    }
}
