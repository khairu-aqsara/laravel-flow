<?php

namespace Ezadev\Flow;

use Illuminate\Support\ServiceProvider;

class EzadevFlowServiceProvider extends ServiceProvider
{

    protected $commands = [
        'Ezadev\Flow\Commands\FlowView'
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
        $this->app->singleton(
            'flow', function($app) {
                return new FlowRegistry($app['config']->get('flow'));
            }
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = $this->configPath();
        $this->publishes([
            $configPath=>config_path('flow.php')
        ],'config');
    }

    protected function configPath(){
        return __DIR__.'/../config/flow.php';
    }
}
