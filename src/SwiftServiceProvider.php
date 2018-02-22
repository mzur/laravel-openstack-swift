<?php

namespace Mzur\Filesystem;

use Cache;
use Storage;
use DateTime;
use OpenStack\OpenStack;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Config as FlyConfig;
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

            $openstack = $this->getOpenStack($params);
            $container = $openstack->objectStoreV1()->getContainer($config['container']);

            return new Filesystem(new SwiftAdapter($container), new FlyConfig([
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

    /**
     * Get the OpenStack instance.
     *
     * @param array $params
     *
     * @return OpenStack
     */
    protected function getOpenStack(array $params)
    {
        $cachedTokenKey = "openstack-swift-token-{$params['user']['name']}-{$params['user']['domain']['name']}";

        // Cache the authentication token to significantly speed up requests.
        // See: http://php-openstack-sdk.readthedocs.io/en/identity-v2/services/identity/v3/tokens.html#cache-authentication-token
        if (Cache::has($cachedTokenKey)) {
            $params['cachedToken'] = Cache::get($cachedTokenKey);
        } else {
            $openstack = new OpenStack(['authUrl' => $params['authUrl']]);
            $token = $openstack->identityV3()->generateToken($params);
            $params['cachedToken'] = $token->export();
            // Convert DateTimeImmutable to DateTime because Cache::put expects
            // the latter to determine the expiration.
            $expires = new DateTime($token->expires->format('c'));
            Cache::put($cachedTokenKey, $params['cachedToken'], $expires);
        }

        return new OpenStack($params);
    }
}
