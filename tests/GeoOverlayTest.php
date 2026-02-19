<?php

namespace Biigle\Tests\Modules\Geo;

use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Volume;
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

        $webmap = GeoOverlay::factory()->webMap()->create();
        $this->assertNotNull($webmap->name);
        $this->assertTrue(is_string($webmap->attrs['layer']));
        $this->assertTrue(is_string($webmap->attrs['url']));
        $this->assertNull($webmap->created_at);
        $this->assertNull($webmap->updated_at);
        $this->assertEquals($webmap->type, 'webmap');
    }

    public function testBuildGeoTiff()
    {
        $volume = Volume::factory()->create();
        $overlay = GeoOverlay::build($volume->id, "test", "geotiff", 0, [
            [-2.9213164328107, 57.096651484989, -2.9185182540292, 57.097626526122],
            [3, 2]
        ]);

        $this->assertEquals($volume->id, $overlay->volume_id);
        $this->assertEquals($overlay->type, 'geotiff');
        $this->assertEquals($overlay->name, 'test');
        $this->assertEquals($overlay->layer_index, 0);
        $this->assertEquals(-2.9213164328107, $overlay->attrs['top_left_lng']);
        $this->assertEquals(57.096651484989, $overlay->attrs['top_left_lat']);
        $this->assertEquals(-2.9185182540292, $overlay->attrs['bottom_right_lng']);
        $this->assertEquals(57.097626526122, $overlay->attrs['bottom_right_lat']);
        $this->assertNull($overlay->created_at);
        $this->assertNull($overlay->updated_at);
        $this->assertEquals(3, $overlay->attrs['width']);
        $this->assertEquals(2, $overlay->attrs['height']);
    }

    public function testBuildWebMap()
    {
        $volume = Volume::factory()->create();
        $overlay = GeoOverlay::build($volume->id, "test", "webmap", 0, [
            [-2.9213164328107, 57.096651484989, -2.9185182540292, 57.097626526122],
            'https://example.com',
            'test'
        ]);

        $this->assertEquals($volume->id, $overlay->volume_id);
        $this->assertEquals($overlay->type, 'webmap');
        $this->assertEquals($overlay->name, 'test');
        $this->assertEquals($overlay->layer_index, 0);
        $this->assertEquals(-2.9213164328107, $overlay->attrs['top_left_lng']);
        $this->assertEquals(57.096651484989, $overlay->attrs['top_left_lat']);
        $this->assertEquals(-2.9185182540292, $overlay->attrs['bottom_right_lng']);
        $this->assertEquals(57.097626526122, $overlay->attrs['bottom_right_lat']);
        $this->assertNull($overlay->created_at);
        $this->assertNull($overlay->updated_at);
        $this->assertEquals('https://example.com', $overlay->attrs['url']);
        $this->assertEquals('test', $overlay->attrs['layer']);
    }

    public function testBuildWebMapWithoutCoords()
    {
        $volume = Volume::factory()->create();
        $overlay = GeoOverlay::build($volume->id, "test", "webmap", 0, [
            [],
            'https://example.com',
            'test'
        ]);

        $this->assertEquals($volume->id, $overlay->volume_id);
        $this->assertEquals($overlay->type, 'webmap');
        $this->assertEquals($overlay->name, 'test');
        $this->assertEquals($overlay->layer_index, 0);
        $this->assertArrayNotHasKey('top_left_lng', $overlay->attrs);
        $this->assertArrayNotHasKey('top_left_lat', $overlay->attrs);
        $this->assertArrayNotHasKey('bottom_right_lng', $overlay->attrs);
        $this->assertArrayNotHasKey('bottom_right_lat', $overlay->attrs);
        $this->assertNull($overlay->created_at);
        $this->assertNull($overlay->updated_at);
        $this->assertEquals('https://example.com', $overlay->attrs['url']);
        $this->assertEquals('test', $overlay->attrs['layer']);
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
