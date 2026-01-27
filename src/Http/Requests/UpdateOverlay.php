<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOverlay extends FormRequest
{

    /**
     * The volume for which the new overlay should be stored.
     *
     * @var Volume
     */
    public $volume;

    /**
     * Key of field which should be updated
     *
     * @var string
     */
    public $updateKey;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        $this->volume = Volume::findOrFail($this->route('id'));

        return $this->user()->can('update', $this->volume);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $overlayCount = GeoOverlay::where('volume_id', $this->volume->id)->count();
        $overlayCount = $overlayCount === 0 ? 0 : $overlayCount - 1;
        return [
            'updated_overlays' => "required|array",
            'updated_overlays.*.id' => "required|integer|gte:0",
            'updated_overlays.*.volume_id' => "required|integer|gte:0",
            'updated_overlays.*.name' => "required|string|max:512",
            'updated_overlays.*.layer_index' => "filled|integer|between:0,$overlayCount",
            'updated_overlays.*.browsing_layer' => "filled|boolean",
        ];
    }

    public function withValidator($validator)
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $validator->after(function ($validator) {
            $this->updateKey = isset($this->input('updated_overlays')[0]['layer_index']) ? 'layer_index' : 'browsing_layer';
            foreach ($this->input('updated_overlays') as $overlay) {
                $hasBothKeys = isset($overlay['layer_index']) && isset($overlay['browsing_layer']);
                $hasNoKeys = !(isset($overlay['layer_index']) || isset($overlay['browsing_layer']));
                if ($hasBothKeys || $hasNoKeys) {
                    $validator->errors()->add('invalidUpdateKey', 'Either layer_index or browsing_layer must be filled.');
                    break;
                }

                if (!isset($overlay[$this->updateKey])) {
                    $validator->errors()->add('invalidRequest', "Request must not contain browsing_layer and layer_index.");
                    break;
                }
            }

            $ids = array_map(fn($e) => $e['id'], $this->input('updated_overlays'));
            $overlays = GeoOverlay::findMany($ids);
            if (count($ids) != $overlays->count()) {
                $missing = $overlays->reject(fn($e) => in_array($e->id, $ids));
                $missing = $missing->map(fn($e) => $e->id)->sort()->join(", ");
                $validator->errors()->add('invalidIds', "GeoOverlay(s) with ids \"$missing\" do not exist.");
            }
        });
    }
}
