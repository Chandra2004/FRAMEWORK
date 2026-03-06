<?php

namespace TheFramework\Middleware;

use TheFramework\App\Core\Lang;

class LanguageMiddleware implements Middleware
{
    public function before()
    {
        // 1. Ambil daftar bahasa yang didukung (mendukung dot notation atau ENV direct string)
        $supportedLocales = config('app.supported_locales', config('APP_SUPPORTED_LOCALES', 'en,id'));
        if (is_string($supportedLocales)) {
            $supportedLocales = array_map('trim', explode(',', $supportedLocales));
        }

        $defaultLocale = config('app.locale', config('APP_LOCALE', 'id'));

        // 2. Cek Query Parameter (?lang=id)
        $requestedLang = request('lang');
        if ($requestedLang && in_array($requestedLang, $supportedLocales)) {
            session(['app_locale' => $requestedLang]);
        }

        // 3. Ambil dari Session atau Default
        $locale = session('app_locale', $defaultLocale);

        // 4. Set Locale di App Engine
        Lang::setLocale($locale);
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}
