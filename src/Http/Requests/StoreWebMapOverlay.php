<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Modules\Geo\GeoOverlay;
use Biigle\Volume;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWebMapOverlay extends FormRequest
{
    /**
     * The volume for which the new overlay should be stored.
     *
     * @var Volume
     */
    public $volume;

    /**
     * Indicates if the validator should stop on the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = true;

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
            'url' => 'required|url:http,https|max:512',
        ];
    }

        
    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $uploaded_attrs = GeoOverlay::where('volume_id', $this->volume['id'])->where('type', 'webmap')->pluck('attrs')->all();
                $uploaded_urls = array_column($uploaded_attrs, 'url');
                // dd($uploaded_urls);
                
                if(in_array($this->input('url'), $uploaded_urls)) {
                    $validator->errors()->add(
                        'url.unique',
                        'The url has already been uploaded.'
                    );
                }
            }
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.unique' => 'The url has already been uploaded.',
        ];
    }
}
