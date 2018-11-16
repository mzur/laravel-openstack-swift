<?php

namespace Mzur\Filesystem;

use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class SwiftServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['filesystem']->extend('swift', function($app, $config) {

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

            return new Filesystem($wrapper, $this->getFlyConfig($config));
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


    /**
     * Create the Flysyystem configuration.
     *
     * @param array $config
     *
     * @return Config
     */
    protected function getFlyConfig($config)
    {
        $flyConfig = new Config([
            'disable_asserts' => array_get($config, 'disableAsserts', false),
        ]);

        $passThroughConfig = [
            'swiftLargeObjectThreshold',
            'swiftSegmentSize',
            'swiftSegmentContainer',
        ];

        foreach ($passThroughConfig as $key) {
            if (isset($config[$key])) {
                $flyConfig->set($key, $config[$key]);
            }
        }

        return $flyConfig;
    }
}
