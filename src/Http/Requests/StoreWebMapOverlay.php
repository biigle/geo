<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Exception;
use Biigle\Volume;
use Illuminate\Support\Str;
use Biigle\Modules\Geo\GeoOverlay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Biigle\Modules\Geo\Services\Support\WebMapSource;
use Biigle\Modules\Geo\Exceptions\WebMapSourceException;

class StoreWebMapOverlay extends FormRequest
{
    /**
     * The volume for which the new overlay should be stored.
     *
     * @var Volume
     */
    public $volume;

    /**
     * The webMapSource to process the xml
     *
     * @var WebMapSource
     */
    public $webmapSource;

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
        $overlayCount = GeoOverlay::where('volume_id', $this->volume->id)->count();
        return [
            'url' => 'required|url:http,https|max:512',
            'layer_index' => "required|integer|between:0,$overlayCount",
        ];
    }

    public function withValidator($validator)
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $validator->after(function ($validator) {
            $url = $this->input('url');
            $host = parse_url($url)['host'];
            // Remove brackets from urls with ip6 adresses
            $host = trim($host, '[]');

            if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
                throw ValidationException::withMessages(['url' => "URL must not contain 'localhost' or any IP adress."]);
            }

            try {
                $this->webmapSource->useUrl($url);
            } catch (WebMapSourceException | Exception $e) {
                $msg = [];

                if ($e instanceof WebMapSourceException) {
                    $msg = $e->getMessageArray();
                }

                throw ValidationException::withMessages($msg);
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
