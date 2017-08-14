<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use File;
use Response;
use ApiTestCase;
use Biigle\Tests\Modules\Geo\GeoOverlayTest;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class GeoOverlayControllerTest extends ApiTestCase
{
    public function testShowFile()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;

        $this->doTestApiRoute('GET', "/api/v1/geo-overlays/{$id}/file");

        $this->beUser();
        $response = $this->get("/api/v1/geo-overlays/{$id}/file");
        $response->assertStatus(403);

        $this->beGuest();
        Response::shouldReceive('download')->once()
            ->with($overlay->path)
            ->andReturn('abc');
        $response = $this->json('GET', "/api/v1/geo-overlays/{$id}/file")
            ->assertSeeText('abc');
        $response->assertStatus(200);
    }

    public function testShowFileNotFound()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;

        $this->beGuest();
        Response::shouldReceive('download')->once()
            ->with($overlay->path)
            ->andThrow(FileNotFoundException::class);
        Response::shouldReceive('json')->passthru();
        $response = $this->json('GET', "/api/v1/geo-overlays/{$id}/file");
        $response->assertStatus(404);
    }

    public function testDestroy()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;

        $this->doTestApiRoute('DELETE', "/api/v1/geo-overlays/{$id}");

        $this->beEditor();
        $response = $this->delete("/api/v1/geo-overlays/{$id}");
        $response->assertStatus(403);

        $this->beAdmin();
        File::shouldReceive('exists')->once()->with($overlay->path)->andReturn(true);
        File::shouldReceive('delete')->once()->with($overlay->path);
        File::shouldReceive('files')->once()->with($overlay->directory)->andReturn([]);
        File::shouldReceive('deleteDirectory')->once()->with($overlay->directory);
        $response = $this->delete("/api/v1/geo-overlays/{$id}");
        $response->assertStatus(200);
    }
}
