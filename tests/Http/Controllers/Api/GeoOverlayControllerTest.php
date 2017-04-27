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
        $this->get("/api/v1/geo-overlays/{$id}/file");
        $this->assertResponseStatus(403);

        $this->beGuest();
        Response::shouldReceive('download')->once()
            ->with($overlay->path)
            ->andReturn('abc');
        $this->json('GET', "/api/v1/geo-overlays/{$id}/file")
            ->see('abc');
        $this->assertResponseOk();
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
        $this->json('GET', "/api/v1/geo-overlays/{$id}/file");
        $this->assertResponseStatus(404);
    }

    public function testDestroy()
    {
        $overlay = GeoOverlayTest::create();
        $overlay->volume_id = $this->volume()->id;
        $overlay->save();
        $id = $overlay->id;

        $this->doTestApiRoute('DELETE', "/api/v1/geo-overlays/{$id}");

        $this->beEditor();
        $this->delete("/api/v1/geo-overlays/{$id}");
        $this->assertResponseStatus(403);

        $this->beAdmin();
        File::shouldReceive('exists')->once()->with($overlay->path)->andReturn(true);
        File::shouldReceive('delete')->once()->with($overlay->path);
        File::shouldReceive('files')->once()->with($overlay->directory)->andReturn([]);
        File::shouldReceive('deleteDirectory')->once()->with($overlay->directory);
        $this->delete("/api/v1/geo-overlays/{$id}");
        $this->assertResponseOk();
    }
}
