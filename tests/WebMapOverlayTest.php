<?php

namespace Biigle\Tests\Modules\Geo;

use Biigle\Modules\Geo\WebMapOverlay;
use Biigle\Tests\VolumeTest;
use Biigle\Volume;
use TestCase;

class WebMapOverlayTest extends TestCase
{
    public static function create()
    {
        $faker = \Faker\Factory::create();
        $model = new WebMapOverlay;
        $model->name = $faker->company();
        $model->layer = $faker->word();
        $model->volume_id = VolumeTest::create()->id;
        $model->url = $faker->url();
        $model->browsing_layer = false;
        $model->context_layer = false;
        $model->save();

        return $model;
    }

    public function testAttributes()
    {
        $model = self::create()->fresh();
        $this->assertNotNull($model->name);
        $this->assertNotNull($model->url);
        $this->assertNotNull($model->layer);
        $this->assertEquals($model->browsing_layer, false);
        $this->assertEquals($model->context_layer, false);
        $this->assertNull($model->created_at);
        $this->assertNull($model->updated_at);
    }

    public function testVolumeOnDeleteCascade()
    {
        $model = self::create();
        $model->volume->delete();
        $this->assertNull($model->fresh());
    }
}
