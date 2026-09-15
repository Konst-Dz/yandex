<?php

namespace App\Modules\Organizations\Http\Requests;

use App\Modules\Organizations\Validation\YandexMapsLinkRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOrganizationLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'max:2048',
                new YandexMapsLinkRule(),
                Rule::unique('organizations', 'url')
                    ->where('user_id', (int) $this->user()?->id)
                    ->ignore($this->route('organization')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'url.unique' => 'This organization card is already connected.',
        ];
    }
}
