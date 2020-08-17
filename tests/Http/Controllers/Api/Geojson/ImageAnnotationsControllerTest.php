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

class ImageAnnotationsControllerTest extends ApiTestCase {
  public function testIndex() {
    $faker = Faker::create();
    $firstProject = $this->project();
    $volume = $this->volume();

    $labelNames = ["sponge", "jellyfish", "starfish"];
    $labels = collect($labelNames)->map(function($name)
    {
      return LabelTest::create(["name"=>$name]);
    });
    $image1 = ImageTest::create([
      'volume_id' => $volume->id,
      'filename'=>"image1.jpg",
      'attrs'=>["size"=>638269,"mimetype"=>"image\/jpeg","width"=>4096,"height"=>3072, "metadata"=>["gps_altitude"=>-4131.9908256880735,"distance_to_ground"=>"6.9","yaw"=>"271"]],
      'lat'=>$faker->latitude(-90,90),
      'lng'=>$faker->longitude(-180, 180)]);
    $annotation = AnnotationTest::create(['image_id' => $image1->id]);
    AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);

    $image2 = ImageTest::create([
      'volume_id' => $volume->id,
      'filename'=>"image2.jpg",
      'attrs'=>["size"=>638269,"mimetype"=>"image\/jpeg","width"=>4096,"height"=>3072, "metadata"=>["gps_altitude"=>-4131.9908256880735,"distance_to_ground"=>Null,"yaw"=>"271"]],
      'lat'=>Null,
      'lng'=>$faker->longitude(-180, 180)]);
    $annotation = AnnotationTest::create(['image_id' => $image2->id]);
    AnnotationLabelTest::create(['annotation_id' => $annotation->id, 'label_id' => $labels->random()->id]);


    $this->doTestApiRoute('GET', "/api/v1/geojson/images/{$image1->id}/annotations");

    $this->beUser();
    $response = $this->get("/api/v1/geojson/images/{$image1->id}/annotations");
    $response->assertStatus(403);

    $this->beEditor();
    $response = $this->get("/api/v1/geojson/images/{$image1->id}/annotations");
    $response->assertStatus(200);

    $this->beGuest();
    $response = $this->get("/api/v1/geojson/images/{$image1->id}/annotations");
    $response->assertStatus(200);

    $this->beExpert();
    $response = $this->get("/api/v1/geojson/images/{$image1->id}/annotations");
    $response->assertStatus(200);

    $this->beAdmin();
    $response = $this->get("/api/v1/geojson/images/{$image1->id}/annotations");
    $response->assertStatus(200);

    $this->beEditor();
    $response = $this->get("/api/v1/geojson/images/{$image2->id}/annotations");
    $response->assertStatus(404);
  }
}
