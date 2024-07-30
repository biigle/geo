<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Modules\Geo\WebMapOverlay;
use Biigle\Tests\Modules\Geo\WebMapOverlayTest;

class WebMapOverlayControllerTest extends ApiTestCase
{

    public function testStoreWebMap() 
    {
        $id = $this->volume()->id;
        
        $overlay = WebMapOverlayTest::create();
        $this->doTestApiRoute('POST', "/api/v1/volumes/{$id}/geo-overlays/webmap");
    }
}