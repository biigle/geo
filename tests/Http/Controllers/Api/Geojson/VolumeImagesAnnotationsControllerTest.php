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

class VolumeImagesAnnotationsControllerTest extends ApiTestCase {
  public function testIndex() {
    $faker = Faker::create();
    $firstProject = $this->project();
    $secondProject = ProjectTest::create();
    $thirdProject = ProjectTest::create(['creator_id' => $this->editor()->id]);

    $volume = $this->volume();
    $volume_2 = VolumeTest::create();
    $volume_3 = VolumeTest::create();
    $secondProject->volumes()->save($volume);
    $thirdProject->volumes()->save($volume_3);

    $labelNames = ["sponge", "jellyfish", "starfish"];
    $labels = collect($labelNames)->map(function($name)
    {
      return LabelTest::create(["name"=>$name]);
    });
    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume->id,
        'filename'=>"$i.jpg",
        'attrs'=>["size"=>638269,"mimetype"=>"image\/jpeg","width"=>4096,"height"=>3072, "metadata"=>["gps_altitude"=>-4131.9908256880735,"distance_to_ground"=>"6.9","yaw"=>"271"]],
        'lat'=>$faker->latitude(-90,90),
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume->id,
        'filename'=>"{$i}0.jpg",
        'attrs'=>["size"=>638269,"mimetype"=>"image\/jpeg","width"=>4096,"height"=>3072, "metadata"=>["gps_altitude"=>-4131.9908256880735,"distance_to_ground"=>Null,"yaw"=>"271"]],
        'lat'=>Null,
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume->id,
        'filename'=>"{$i}1.jpg",
        'attrs'=>["size"=>638269,"mimetype"=>"image\/jpeg","width"=>4096,"height"=>3072, "metadata"=>["gps_altitude"=>-4131.9908256880735,"distance_to_ground"=>Null,"yaw"=>"271"]],
        'lat'=>$faker->latitude(-90,90),
        'lng'=>Null]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume->id,
        'filename'=>"{$i}2.jpg",
        'attrs'=>["size"=>638269,"mimetype"=>"image\/jpeg","width"=>4096,"height"=>3072, "metadata"=>["gps_altitude"=>-4131.9908256880735,"distance_to_ground"=>Null,"yaw"=>Null]],
        'lat'=>$faker->latitude(-90,90),
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume->id,
        'filename'=>"{$i}3.jpg",
        'lat'=>$faker->latitude(-90,90),
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    for($i=0;$i<5;$i++) {
      $image = ImageTest::create([
        'volume_id' => $volume_2->id,
        'filename'=>"{$i}4.jpg",
        'lat'=>$faker->latitude(-90,90),
        'lng'=>$faker->longitude(-180, 180)]);
      $annotation = AnnotationTest::create(['image_id' => $image->id]);
      AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);
    }

    $this->doTestApiRoute('GET', "/api/v1/geojson/volumes/{$volume->id}/annotations");

    $this->beUser();
    $response = $this->get("/api/v1/geojson/volumes/{$volume->id}/annotations");
    $response->assertStatus(403);

    $this->beEditor();
    $response = $this->get("/api/v1/geojson/volumes/{$volume_3->id}/annotations");
    $response->assertStatus(404);

    $this->beEditor();
    $response = $this->get("/api/v1/geojson/volumes/{$volume->id}/annotations");
    $response->assertStatus(200);

    $this->beAdmin();
    $response = $this->get("/api/v1/geojson/volumes/{$volume->id}/annotations");
    $response->assertStatus(200);

    $vol_1_images_id = $volume->images()->where(function($query) {
      $columns = ["lat", "lng", "attrs->metadata->distance_to_ground","attrs->metadata->yaw"];
      foreach($columns as $column) {
        $query->whereNotNull($column);
      }
    })->orderBy('id')->pluck('id')->all();
    $vol_2_images_id = $volume_2->images()->where(function($query) {
      $columns = ["lat", "lng", "attrs->metadata->distance_to_ground","attrs->metadata->yaw"];
      foreach($columns as $column) {
        $query->whereNotNull($column);
      }
    })->orderBy('id')->pluck('id')->all();
    $resp_images_id = array_map(function($a)
    {
      return $a->properties->image_id;
    }, json_decode($response->getContent())->features);
    sort($resp_images_id);

    $this->assertEquals($vol_1_images_id, $resp_images_id);
    //
    $this->assertFalse($vol_2_images_id === $resp_images_id);
  }
}
