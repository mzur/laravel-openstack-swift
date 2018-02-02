<?php

namespace Mzur\Filesystem;

use OpenStack\OpenStack;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Nimbusoft\Flysystem\OpenStack\SwiftAdapter;

class SwiftServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->make('filesystem')->extend('swift', function($app, $config) {
            $openstack = new OpenStack([
                'authUrl' => $config['authUrl'],
                'region' => $config['region'],
                'user' => [
                    'id' => $config['user'],
                    'password' => $config['password'],
                ],
                'scope' => ['project' => ['id' => $config['projectId']]],
            ]);

            $container = $openstack->objectStoreV1()->getContainer($config['container']);

            return new Filesystem(new SwiftAdapter($container));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
