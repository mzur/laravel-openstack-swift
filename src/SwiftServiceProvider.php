<?php

namespace Mzur\Filesystem;

use Cache;
use Storage;
use DateTime;
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
        Storage::extend('swift', function($app, $config) {
            $params = [
                'authUrl' => $config['authUrl'],
                'region' => $config['region'],
                'user' => [
                    'name' => $config['user'],
                    'password' => $config['password'],
                    'domain' => ['name' => $config['domain']],
                ],
            ];

            $cachedTokenKey = "openstack-swift-token-{$config['user']}-{$config['domain']}";

            // Cache the authentication token to significantly speed up requests.
            // See: http://php-openstack-sdk.readthedocs.io/en/identity-v2/services/identity/v3/tokens.html#cache-authentication-token
            if (Cache::has($cachedTokenKey)) {
                $params['cachedToken'] = Cache::get($cachedTokenKey);
            } else {
                $openstack = new OpenStack(['authUrl' => $config['authUrl']]);
                $token = $openstack->identityV3()->generateToken($params);
                $params['cachedToken'] = $token->export();
                // Convert DateTimeImmutable to DateTime because Cache::put expects
                // the latter to determine the expiration.
                $expires = new DateTime($token->expires->format('c'));
                Cache::put($cachedTokenKey, $params['cachedToken'], $expires);
            }

            $openstack = new OpenStack($params);
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
