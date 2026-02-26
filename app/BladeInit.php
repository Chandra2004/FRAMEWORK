<?php

namespace TheFramework;

use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

class BladeInit
{
    private static $blade;
    private static $isInitializing = false;

    public static function init()
    {
        if (self::$blade) {
            return self::$blade;
        }

        if (self::$isInitializing) {
            // Prevent recursion if error occurs during initialization
            return null;
        }

        self::$isInitializing = true;

        try {
            $filesystem = new Filesystem();
            $resolver = new EngineResolver();

            // Ensure cache directory exists (using consistent framework path)
            $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
            $cachePath = $root . '/storage/framework/views';
            if (!is_dir($cachePath)) {
                if (!@mkdir($cachePath, 0777, true) && !is_dir($cachePath)) {
                    throw new \Exception("Failed to create Blade cache directory: $cachePath");
                }
            } else if (!is_writable($cachePath)) {
                @chmod($cachePath, 0777);
            }

            $resolver->register('blade', function () use ($filesystem, $cachePath) {
                $compiler = new BladeCompiler($filesystem, $cachePath);

                // @csrf
                $compiler->directive('csrf', function () {
                    return "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"' . csrf_token() . '\">'; ?>";
                });

                // @auth
                $compiler->if('auth', function () {
                    return session('user') !== null;
                });

                // @guest
                $compiler->if('guest', function () {
                    return session('user') === null;
                });

                // @error('field_name')
                $compiler->directive('error', function ($expression) {
                    return "<?php if (has_error($expression)): ?>";
                });

                $compiler->directive('enderror', function () {
                    return "<?php endif; ?>";
                });

                // --- NEW POWERFUL DIRECTIVES ---

                // @rupiah($amount)
                $compiler->directive('rupiah', function ($expression) {
                    return "<?php echo rupiah($expression); ?>";
                });

                // @session('key') ... @endsession
                $compiler->directive('session', function ($expression) {
                    return "<?php if (session($expression)): ?>";
                });
                $compiler->directive('endsession', function () {
                    return "<?php endif; ?>";
                });

                // @config('app.name')
                $compiler->directive('config', function ($expression) {
                    return "<?php echo config($expression); ?>";
                });

                return new CompilerEngine($compiler, $filesystem);
            });

            $resolver->register('php', function () {
                return new PhpEngine(new Filesystem);
            });

            $root = defined('ROOT_DIR') ? ROOT_DIR : dirname(__DIR__);
            $viewPaths = [
                $root . '/resources/views',
                // $root . '/app/App/Internal/Views', // REMOVED: Use namespace instead to prevent conflicts
            ];
            $finder = new FileViewFinder($filesystem, $viewPaths);

            // Register Internal Namespace
            // Usage: View::render('Internal::errors.404')
            $finder->addNamespace('Internal', $root . '/app/App/Internal/Views');

            self::$blade = new Factory(
                $resolver,
                $finder,
                new Dispatcher()
            );
        } finally {
            self::$isInitializing = false;
        }
        return self::$blade;
    }

    public static function getInstance()
    {
        return self::init();
    }
}
