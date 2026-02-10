<?php

namespace TheFramework\Middleware;

use TheFramework\App\Lang;

class LanguageMiddleware implements Middleware
{
    public function before()
    {
        // 1. Check Query Parameter (?lang=id)
        if (isset($_GET['lang'])) {
            $lang = $_GET['lang'];
            // Validasi input (hanya izinkan 'en' atau 'id' untuk keamanan)
            if (in_array($lang, ['en', 'id'])) {
                $_SESSION['app_locale'] = $lang;
            }
        }

        // 2. Check Session
        $locale = $_SESSION['app_locale'] ?? 'en'; // Default English

        // 3. Set Locale di App
        Lang::setLocale($locale);
    }

    public function after()
    {
        // Logic after controller (optional)
    }
}
