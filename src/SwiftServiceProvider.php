<?php

namespace Mzur\Filesystem;

use Biigle\CachedOpenStack\OpenStack;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;

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
            $options = $this->getOsOptions($config);
            $container = (new OpenStack($app->make('cache'), $options))
                ->objectStoreV1()
                ->getContainer($config['container']);

            $prefix = Arr::get($config, 'prefix', '');
            $url = Arr::get($config, 'url', '');
            $key = Arr::get($config, 'tempUrlKey', false);

            if ($key) {
                $adapter = new TempUrlSwiftAdapter($container, $key, $prefix, $url);
            } else {
                $adapter = new SwiftAdapter($container, $prefix, $url);
            }

            $flyConfig = $this->getFlyConfig($config);

            return new FilesystemAdapter(
                new Filesystem($adapter, $flyConfig),
                $adapter,
                $flyConfig
            );
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
            'debugLog' => Arr::get($config, 'debugLog', false),
            'logger' => Arr::get($config, 'logger', null),
            'messageFormatter' => Arr::get($config, 'messageFormatter', null),
            'requestOptions' => Arr::get($config, 'requestOptions', []),
        ];

        if (array_key_exists('projectId', $config)) {
            $options['scope'] = ['project' => ['id' => $config['projectId']]];
        }

        if (array_key_exists('ttl', $config)) {
            $options['cacheOptions'] = ['ttl' => $config['ttl']];
        }

        return $options;
    }

    /**
     * Create the Flysystem configuration.
     *
     * @param array $config
     *
     * @return array
     */
    protected function getFlyConfig($config)
    {
        $flyConfig = [];

        $passThroughConfig = [
            'swiftLargeObjectThreshold',
            'swiftSegmentSize',
            'swiftSegmentContainer',
        ];

        foreach ($passThroughConfig as $key) {
            if (isset($config[$key])) {
                $flyConfig[$key] = $config[$key];
            }
        }

        return $flyConfig;
    }
}
