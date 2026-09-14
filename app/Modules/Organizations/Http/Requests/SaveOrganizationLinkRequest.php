<?php

namespace App\Modules\Organizations\Http\Requests;

use App\Modules\Organizations\Validation\YandexMapsLinkRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveOrganizationLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', new YandexMapsLinkRule()],
        ];
    }
}
