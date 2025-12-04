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
        return [
            'browsing_layer' => 'filled|boolean',
            'layer_index' => 'filled|integer|gte:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (count($this->all()) === 0) {
                $validator->errors()->add('invalidRequest', 'The request body is missing. No updates performed.');
            }

            if ($this->has('layer_index')) {
                $idx = $this->input('layer_index');
                $overlayCount = GeoOverlay::count() - 1;
                if ($idx > $overlayCount) {
                    $validator->errors()->add('invalidLayerIndex', 'The layer index is invalid.');
                }
            }
        });
    }
}
