<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Tests\ImageTest;
use Biigle\Tests\VolumeTest;

class FileCoordinatesControllerTest extends ApiTestCase
{
    public function testIndexImage()
    {
        $id = $this->volume()->id;
        $secondVolume = VolumeTest::create();

        $image1 = ImageTest::create([
            'volume_id' => $id,
            'filename' => '1.jpg',
            'id' => 1,
            'lng' => -88.464813199987,
            'lat' => -7.0772818384026,
        ]);

        $secondImage = ImageTest::create([
            'volume_id' => $secondVolume->id,
            'filename' => '2.jpg',
            'id' => 2,
            'lng' => -66.666,
            'lat' => -7.0777,
        ]);

        $this->doTestApiRoute('GET', "/api/v1/volumes/{$id}/coordinates");

        $this->beUser();
        $this->get("/api/v1/volumes/{$id}/coordinates")->assertStatus(403);

        $this->beGuest();
        $this->get("/api/v1/volumes/{$id}/coordinates")
            ->assertExactJson([
                [
                    'id' => 1,
                    'lat' => -7.0772818384026,
                    'lng' => -88.464813199987
                ]
            ])
            ->assertStatus(200);
    }
}
