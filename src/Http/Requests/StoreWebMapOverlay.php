<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
use Illuminate\Support\Str;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\Geo\Services\Support\WebMapSource;

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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('url')) {
                throw ValidationException::withMessages(['invalid' => 'Invalid request. Web map URL is missing']);
            }
            $this->webmapSource = new WebMapSource($this->input('url'));

            $overlay = GeoOverlay::where('volume_id', $this->volume->id)
                ->where('type', 'webmap')
                ->whereJSONContains('attrs', ['url' => $this->webmapSource->baseUrl]);

            if ($overlay->exists()) {
                $urlShort = Str::limit($this->webmapSource->baseUrl, 80);
                $name = $overlay->first()->name;
                $validator->errors()->add(
                    'uniqueUrl',
                    "The url \"{$urlShort}\" has already been uploaded (Filename: \"{$name}\").",
                );
            }
        });
    }
}
