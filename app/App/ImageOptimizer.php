<?php
    namespace {{NAMESPACE}}\App;

    use Spatie\ImageOptimizer\OptimizerChainFactory;

    class ImageOptimizer
    {
        public static function optimizeImage($filePath)
        {
            $optimizerChain = OptimizerChainFactory::create();

            try {
                $optimizerChain->optimize($filePath);
                echo "Image optimized: $filePath";
            } catch (\Exception $e) {
                echo "Failed to optimize image: " . $e->getMessage();
            }
        }
    }
?> 
