<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
use Illuminate\Foundation\Http\FormRequest;

class StoreGeotiffOverlay extends FormRequest
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
     * make sure th file is in tiff format and not bigger than 50GB (52.430.000 kilobytes)
     *
     * @return array
     */
    public function rules()
    {
        return [
            'geotiff' => 'required|file|max:52430000|mimetypes:image/tiff',
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