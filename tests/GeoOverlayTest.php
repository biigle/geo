<?php

namespace Biigle\Tests\Modules\Geo;

use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Tests\VolumeTest;
use Biigle\Volume;
use TestCase;

class GeoOverlayTest extends TestCase
{
    public static function create()
    {
        $faker = \Faker\Factory::create();
        $model = new GeoOverlay;
        $model->name = $faker->company();
        $model->top_left_lng = $faker->randomNumber();
        $model->top_left_lat = $faker->randomNumber();
        $model->bottom_right_lng = $faker->randomNumber();
        $model->bottom_right_lat = $faker->randomNumber();
        $model->volume_id = VolumeTest::create()->id;
        $model->browsing_layer = false;
        $model->context_layer = false;
        $model->attrs = ['width' => $faker->randomNumber(), 'height' => $faker->randomNumber()];
        $model->save();

        return $model;
    }

    public function testAttributes()
    {
        $model = self::create()->fresh();
        $this->assertNotNull($model->name);
        $this->assertTrue(is_float($model->top_left_lng));
        $this->assertTrue(is_float($model->top_left_lat));
        $this->assertTrue(is_float($model->bottom_right_lng));
        $this->assertTrue(is_float($model->bottom_right_lat));
        $this->assertNull($model->created_at);
        $this->assertNull($model->updated_at);
        $this->assertTrue(is_int($model->attrs['width']));
        $this->assertTrue(is_int($model->attrs['height']));
    }

    public function testVolumeOnDeleteCascade()
    {
        $model = self::create();
        $model->volume->delete();
        $this->assertNull($model->fresh());
    }

    public function testPathAttribute()
    {
        $model = self::create();
        $this->assertEquals("{$model->id}/{$model->id}_original", $model->getPathAttribute());
    }
}