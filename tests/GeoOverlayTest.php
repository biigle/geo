<?php

namespace Biigle\Tests\Modules\Geo;

use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\VolumeTest;
use Biigle\Volume;
use TestCase;

class GeoOverlayTest extends TestCase
{
    public static function createGeotiffOverlay()
    {
        $faker = \Faker\Factory::create();
        $model = new GeoOverlay;
        $model->name = $faker->company();
        $model->volume_id = VolumeTest::create()->id;
        $model->type = 'geotiff';
        $model->browsing_layer = false;
        $model->context_layer = false;
        $model->layer_index = null;
        $model->attrs = [
            'top_left_lng' => $faker->randomFloat(),
            'top_left_lat' => $faker->randomFloat(),
            'bottom_right_lng' => $faker->randomFloat(),
            'bottom_right_lat' => $faker->randomFloat(),
            'width' => $faker->randomNumber(), 
            'height' => $faker->randomNumber()
        ];
        $model->save();

        return $model;
    }

    public static function createWebMapOverlay()
    {
        $faker = \Faker\Factory::create();
        $model = new GeoOverlay;
        $model->name = $faker->company();
        $model->volume_id = VolumeTest::create()->id;
        $model->type = 'webmap';
        $model->browsing_layer = false;
        $model->context_layer = false;
        $model->layer_index = null;
        $model->attrs = [
            'layers' => [$faker->word(), $faker->word()],
            'url' => $faker->url()
        ];
        $model->save();

        return $model;
    }

    public function testAttributes()
    {
        $geotiff = self::createGeotiffOverlay()->fresh();
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

        $webmap = self::createWebMapOverlay()->fresh();
        $this->assertNotNull($webmap->name);
        $this->assertTrue(is_array($webmap->attrs['layers']));
        $this->assertTrue(is_string($webmap->attrs['url']));
        $this->assertNull($webmap->created_at);
        $this->assertNull($webmap->updated_at);
        $this->assertEquals($webmap->type, 'webmap');
    }

    public function testVolumeOnDeleteCascade()
    {
        $model = self::createGeotiffOverlay();
        $model->volume->delete();
        $this->assertNull($model->fresh());
    }

    public function testPathAttribute()
    {
        $model = self::createGeotiffOverlay();
        $this->assertEquals("{$model->id}/{$model->id}_original", $model->getPathAttribute());
    }
}