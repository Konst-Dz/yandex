<?php

namespace App\Modules\Organizations\Validation;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexMapsLinkRule implements ValidationRule
{
    private const ALLOWED_HOSTS = ['yandex.ru', 'www.yandex.ru', 'maps.yandex.ru', 'ya.ru'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $url = (string) $value;
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (!in_array($host, self::ALLOWED_HOSTS, true) || !str_starts_with($path, '/maps/')) {
            $fail('The link must point to an organization card on Yandex Maps (yandex.ru/maps).');
        }
    }
}
