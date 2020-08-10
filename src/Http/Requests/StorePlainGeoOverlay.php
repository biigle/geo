<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
use Illuminate\Foundation\Http\FormRequest;

class StorePlainGeoOverlay extends FormRequest
{
    /**
     * The volume for which the new overlay should be stored.
     *
     * @var Volume
     */
    public $volume;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->volume = Volume::findOrFail($this->route('id'));

        return $this->user()->can('update', $this->volume);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'filled|max:512',
            'file' => 'required|file|max:10000|mimetypes:image/jpeg,image/png,image/tiff',
            'top_left_lat' => 'required|numeric|max:90|min:-90',
            'top_left_lng' => 'required|numeric|max:180|min:-180',
            'bottom_right_lat' => 'required|numeric|max:90|min:-90',
            'bottom_right_lng' => 'required|numeric|max:180|min:-180',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->volume->isVideoVolume()) {
                $validator->errors()->add('id', 'Geo overlays are not available for video volumes.');
            }
        });
    }
}
