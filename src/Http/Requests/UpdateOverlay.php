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
        $overlayCount = GeoOverlay::where('volume_id', $this->volume->id)->count();
        return [
            'browsing_layer' => 'filled|boolean',
            'layer_index' => "filled|integer|between:0,$overlayCount",
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (count($this->all()) === 0) {
                $validator->errors()->add('invalidRequest', 'The request body is missing. No updates performed.');
            }
        });
    }
}
