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

    public static function init()
    {
        if (!self::$blade) {
            $filesystem = new Filesystem();
            $resolver = new EngineResolver();

            // Ensure cache directory exists
            $cachePath = dirname(__DIR__) . '/storage/cache/views';
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
            }

            $resolver->register('blade', function () use ($filesystem, $cachePath) {
                $compiler = new BladeCompiler($filesystem, $cachePath);

                // @csrf
                $compiler->directive('csrf', function () {
                    return "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"' . \\TheFramework\\Helpers\\Helper::generateCsrfToken() . '\">'; ?>";
                });

                // @auth
                $compiler->if('auth', function () {
                    // Cek session user login standar
                    return isset($_SESSION['user']);
                });

                // @guest
                $compiler->if('guest', function () {
                    return !isset($_SESSION['user']);
                });

                // @error('field_name')
                $compiler->directive('error', function ($expression) {
                    return "<?php if (\\TheFramework\\Helpers\\Helper::has_error($expression)): ?>";
                });

                $compiler->directive('enderror', function () {
                    return "<?php endif; ?>";
                });

                return new CompilerEngine($compiler, $filesystem);
            });

            $resolver->register('php', function () {
                return new PhpEngine(new Filesystem);
            });

            $viewPaths = [
                dirname(__DIR__) . '/resources/views',
            ];
            $finder = new FileViewFinder($filesystem, $viewPaths);

            self::$blade = new Factory(
                $resolver,
                $finder,
                new Dispatcher()
            );
        }
        return self::$blade;
    }

    public static function getInstance()
    {
        return self::init();
    }
}
