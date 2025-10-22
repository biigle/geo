<?php

namespace Biigle\Modules\Geo\Database\factories;

use Biigle\Volume;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeoOverlayFactory extends Factory
{
    protected $model = GeoOverlay::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company(),
            'volume_id' => Volume::factory(),
            'type' => 'geotiff',
            'browsing_layer' => false,
            'context_layer' => false,
            'layer_index' => null,
            'attrs' => [
                'top_left_lng' => $this->faker->randomFloat(),
                'top_left_lat' => $this->faker->randomFloat(),
                'bottom_right_lng' => $this->faker->randomFloat(),
                'bottom_right_lat' => $this->faker->randomFloat(),
                'width' => $this->faker->randomNumber(),
                'height' => $this->faker->randomNumber()
            ]
        ];
    }

    public function webMap()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'webmap',
                'attrs' => [
                    'layers' => [$this->faker->word(), $this->faker->word()],
                    'url' => $this->faker->url()
                ]
            ];
        });
    }
}
