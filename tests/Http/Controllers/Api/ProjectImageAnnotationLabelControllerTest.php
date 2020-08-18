<?php

namespace Biigle\Tests\Modules\Geo\Http\Controllers\Api;

use ApiTestCase;
use Biigle\Tests\ImageAnnotationLabelTest;
use Biigle\Tests\ImageAnnotationTest;
use Biigle\Tests\ImageTest;
use Biigle\Tests\LabelTest;

class ProjectImageAnnotationLabelControllerTest extends ApiTestCase
{
    public function testIndex()
    {
        $pid = $this->project()->id;

        $image = ImageTest::create(['volume_id' => $this->volume()->id]);
        $annotation = ImageAnnotationTest::create(['image_id' => $image->id]);
        $label = LabelTest::create();
        ImageAnnotationLabelTest::create([
            'annotation_id' => $annotation->id,
            'label_id' => $label->id,
        ]);
        // image ID should be returned only once, no matter how often the label is present
        ImageAnnotationLabelTest::create([
            'annotation_id' => $annotation->id,
            'label_id' => $label->id,
        ]);

        $lid = $label->id;

        // this image shouldn't appear
        $image2 = ImageTest::create([
            'volume_id' => $this->volume()->id,
            'filename' => 'b.jpg',
        ]);
        $annotation = ImageAnnotationTest::create(['image_id' => $image2->id]);
        ImageAnnotationLabelTest::create([
            'annotation_id' => $annotation->id,
            'user_id' => $this->admin()->id,
        ]);

        $this->doTestApiRoute('GET', "/api/v1/projects/{$pid}/images/filter/annotation-label/{$lid}");

        $this->beUser();
        $this->get("/api/v1/projects/{$pid}/images/filter/annotation-label/{$lid}")
            ->assertStatus(403);

        $this->beGuest();
        $this->get("/api/v1/projects/{$pid}/images/filter/annotation-label/{$lid}")
            ->assertStatus(200)
            ->assertExactJson([$image->id]);
    }
}
