<?php

namespace Biigle\Tests\Modules\Geo;

use Biigle\Modules\Geo\GeoOverlay;
use TestCase;

class GeoOverlayTest extends TestCase
{
    public function testAttributes()
    {
        $geotiff = GeoOverlay::factory()->create();
        $this->assertNotNull($geotiff->name);
        $this->assertTrue(is_float(floatval($geotiff->attrs['top_left_lng'])));
        $this->assertTrue(is_float(floatval($geotiff->attrs['top_left_lat'])));
        $this->assertTrue(is_float(floatval($geotiff->attrs['bottom_right_lng'])));
        $this->assertTrue(is_float(floatval($geotiff->attrs['bottom_right_lat'])));
        $this->assertNull($geotiff->created_at);
        $this->assertNull($geotiff->updated_at);
        $this->assertTrue(is_int($geotiff->attrs['width']));
        $this->assertTrue(is_int($geotiff->attrs['height']));
        $this->assertEquals($geotiff->type, 'geotiff');

        $webmap = GeoOverlay::factory(true)->create();
        $this->assertNotNull($webmap->name);
        $this->assertTrue(is_array($webmap->attrs['layers']));
        $this->assertTrue(is_string($webmap->attrs['url']));
        $this->assertNull($webmap->created_at);
        $this->assertNull($webmap->updated_at);
        $this->assertEquals($webmap->type, 'webmap');
    }

    public function testVolumeOnDeleteCascade()
    {
        $model = GeoOverlay::factory()->create();
        $model->volume->delete();
        $this->assertNull($model->fresh());
    }

    public function testPathAttribute()
    {
        $model = GeoOverlay::factory()->create();
        $this->assertEquals("{$model->id}/{$model->id}_original.tif", $model->getPathAttribute());
    }
}