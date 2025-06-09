<?php
    namespace {{NAMESPACE}};

    use Illuminate\View\Factory;
    use Illuminate\Events\Dispatcher;
    use Illuminate\View\Engines\EngineResolver;
    use Illuminate\View\Engines\PhpEngine;
    use Illuminate\View\Engines\CompilerEngine;
    use Illuminate\View\FileViewFinder;
    use Illuminate\Filesystem\Filesystem;
    use Illuminate\View\Compilers\BladeCompiler;

    class BladeInit {
        private static $blade;

        public static function init() {
            if (!self::$blade) {
                $filesystem = new Filesystem();
                $resolver = new EngineResolver();

                $resolver->register('blade', function () use ($filesystem) {
                    $compiler = new BladeCompiler($filesystem, __DIR__ . '/Storage/cache/views');
                    $compiler->directive('csrf', function () {
                        return "<?php echo '<input type=\"hidden\" name=\"_token\" value=\"' . \$_SESSION['csrf_token'] . '\">'; ?>";
                    });                    
                    return new CompilerEngine($compiler, $filesystem);
                });

                $resolver->register('php', function () {
                    return new PhpEngine();
                });

                // Tambahkan services/ sebagai path tambahan untuk Blade
                $viewPaths = [
                    dirname(__DIR__) . '/resources/Views',
                    dirname(__DIR__) . '/services', // Tambahkan services/
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

        public static function getInstance() {
            return self::init();
        }
    }
?>