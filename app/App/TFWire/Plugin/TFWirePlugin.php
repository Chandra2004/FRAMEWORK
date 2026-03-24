<?php

namespace TheFramework\App\TFWire\Plugin;

/**
 * Base class for TFWire plugins
 * 
 * Usage:
 *   class MyPlugin extends TFWirePlugin {
 *       public function register(PluginManager $manager): void {
 *           $manager->on('beforeAction', function($component, $action) {
 *               // Your logic here
 *           });
 *       }
 *   }
 */
abstract class TFWirePlugin
{
    abstract public function register(PluginManager $manager): void;
}
