<?php

namespace Mzur\Filesystem;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Config as FlyConfig;

class SwiftServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('swift', function($app, $config) {

            $params = [
                'authUrl' => $config['authUrl'],
                'region' => $config['region'],
                'user' => [
                    'name' => $config['user'],
                    'password' => $config['password'],
                    'domain' => ['name' => $config['domain']],
                ],
                'debugLog' => array_get($config, 'debugLog', false),
                'logger' => array_get($config, 'logger', null),
                'messageFormatter' => array_get($config, 'messageFormatter', null),
                'requestOptions' => array_get($config, 'requestOptions', []),
            ];

            if (array_key_exists('projectId', $config)) {
                $params['scope'] = ['project' => ['id' => $config['projectId']]];
            }

            $wrapper = new SwiftAdapterWrapper($params, $config['container']);

            return new Filesystem($wrapper, new FlyConfig([
                'disable_asserts' => array_get($config, 'disableAsserts', false),
            ]));
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
