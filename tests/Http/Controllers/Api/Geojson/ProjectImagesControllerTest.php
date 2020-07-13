<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api\Geojson;

use ApiTestCase;
use Faker\Factory as Faker;
use Biigle\{Label, Image, Annotation, AnnotationLabel};
use GeoJson\GeoJson as GeoJson;
use Biigle\Tests\ProjectTest;
use Biigle\Tests\VolumeTest;
use Biigle\Tests\LabelTest;
use Biigle\Tests\ImageTest;
use Biigle\Tests\AnnotationTest;
use Biigle\Tests\AnnotationLabelTest;

class ProjectImagesControllerTest extends ApiTestCase
{
  public function testIndex()
  {
    $faker = Faker::create();
    $firstProject = $this->project();
    $secondProject = ProjectTest::create();

    $volume = $this->volume();
    $volume_2 = VolumeTest::create();
    $secondProject->volumes()->save($volume);


    $labelNames = ["sponge", "jellyfish", "starfish"];
    $labels = collect($labelNames)->map(function($name)
    {
      return LabelTest::create(["name"=>$name]);
    });
    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume->id,
        'filename'=>"$i.jpg",
        'lat'=>$faker->latitude(-90,90),
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume_2->id,
        'filename'=>"{$i}0.jpg",
        'lat'=>$faker->latitude(-90,90),
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    $this->doTestApiRoute('GET', "/api/v1/geojson/projects/{$firstProject->id}/images");

    $this->beUser();
    $response = $this->get("/api/v1/geojson/projects/{$firstProject->id}/images");
    $response->assertStatus(403);

    $this->beEditor();
    $response = $this->get("/api/v1/geojson/projects/{$firstProject->id}/images");
    $response->assertStatus(200);

    $this->beAdmin();
    $response = $this->get("/api/v1/geojson/projects/{$firstProject->id}/images");
    $response->assertStatus(200);

    $vol_1_images_id = $volume->images->sort()->pluck('id')->all();
    $vol_2_images_id = $volume_2->images->sort()->pluck('id')->all();
    $resp_images_id = array_map(function($a)
    {
      return $a->properties->_id;
    }, json_decode($response->getContent())->features);
    sort($resp_images_id);
    $this->assertEquals($vol_1_images_id, $resp_images_id);

    $this->assertFalse($vol_2_images_id === $resp_images_id);
  }
}
