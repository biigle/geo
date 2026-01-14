<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
use Exception;
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

    public function __construct(WebMapSource $webmapSource)
    {
        $this->webmapSource = $webmapSource;
    }

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
                throw ValidationException::withMessages(['invalidRequest' => 'The Web map URL is missing']);
            }

            parse_str(urldecode($this->input('url')), $output);
            $hasMultipleLayer = !empty($output['layers']) && Str::contains($output['layers'], ",");
            if ($hasMultipleLayer) {
                throw ValidationException::withMessages(['tooManyLayers' => "The url must not contain more than one layer."]);
            }

            try {
                $this->webmapSource->useUrl($this->input('url'));
            } catch (Exception $e) {
                throw ValidationException::withMessages(['invalidWMS' => "The url does not lead to a WMS resource."]);
            }

            $overlay = GeoOverlay::where('volume_id', $this->volume->id)
                ->where('type', 'webmap')
                ->whereJSONContains('attrs', [
                    'url' => $this->webmapSource->baseUrl,
                    'layer' => $this->webmapSource->getLayer()[1],
                ]);

            if ($overlay->exists()) {
                $name = Str::limit($overlay->first()->name, 40);
                $validator->errors()->add(
                    'uniqueUrl',
                    "The resource \"{$name}\" has already been uploaded.",
                );
            }
        });
    }
}
