<?php

namespace Biigle\Modules\Geo\Http\Requests;

use Biigle\Volume;
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
            'layer_type' => 'filled|string',
            'value' => 'filled|boolean'
        ];
    }
}
