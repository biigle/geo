<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Views;

use ApiTestCase;
use Biigle\Tests\ImageTest;

class ProjectControllerTest extends ApiTestCase
{
    public function testShow()
    {
        $id = $this->project()->id;

        // not logged in
        $response = $this->get("projects/{$id}/geo");
        $response->assertStatus(302);

        // doesn't belong to project
        $this->beUser();
        $response = $this->get("projects/{$id}/geo");
        $response->assertStatus(403);

        $this->beEditor();
        $response = $this->get("projects/{$id}/geo");
        $response->assertStatus(404);

        ImageTest::create([
            'lng' => 5.5,
            'lat' => 5.5,
            'volume_id' => $this->volume()->id,
        ]);
        $this->volume()->flushGeoInfoCache();

        $response = $this->get("projects/{$id}/geo");
        $response->assertStatus(200);
    }
}
