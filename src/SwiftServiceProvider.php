<?php

namespace Mzur\Filesystem;

use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use Biigle\CachedOpenStack\OpenStack;
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
            $options = $this->getOsOptions($config);
            $container = (new OpenStack($app['cache'], $options))
                ->objectStoreV1()
                ->getContainer($config['container']);

            $prefix = array_get($config, 'prefix', null);
            $url = array_get($config, 'url', null);
            $adapter = new SwiftAdapter($container, $prefix, $url);

            return new Filesystem($adapter, $this->getFlyConfig($config));
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
     * Get the OpenStack options.
     *
     * @param array $config
     *
     * @return array
     */
    protected function getOsOptions($config)
    {
        $options = [
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
            $options['scope'] = ['project' => ['id' => $config['projectId']]];
        }

        return $options;
    }

    /**
     * Create the Flysystem configuration.
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
