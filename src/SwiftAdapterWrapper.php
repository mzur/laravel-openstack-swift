<?php

namespace Mzur\Filesystem;

use Cache;
use DateTime;
use DateTimeImmutable;
use OpenStack\OpenStack;
use League\Flysystem\Config;
use League\Flysystem\AdapterInterface;
use Nimbusoft\Flysystem\OpenStack\SwiftAdapter;

/**
 * Wrapper for SwiftAdapter to take care of authentication token renewal in long running
 * scripts (like Laravel queue workers).
 */
class SwiftAdapterWrapper implements AdapterInterface
{
    /**
     * OpenStack parameters
     *
     * @var array
     */
    protected $params;

    /**
     * Swift container name
     *
     * @var string
     */
    protected $container;

    /**
     * Date when the currently used OpenStack authentication token expires.
     *
     * @var DateTimeImmutable
     */
    protected $expires;

    /**
     * The actual Swift adapter to pass method calls through.
     *
     * @var SwiftAdapter
     */
    protected $adapter;

    /**
     * Create a new instance
     *
     * @param array $params OpenStack parameters
     * @param string $container Swift container name
     */
    public function __construct(array $params, string $container)
    {
        $this->params = $params;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }
    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        return $this->passthru(__FUNCTION__, func_get_args());
    }

    /**
     * Pass method call through to the Swift adapter.
     *
     * @param string $method Method name
     * @param array $args Method arguments
     *
     * @return mixed
     */
    protected function passthru($method, $args)
    {
        if ($this->isTokenExpired()) {
            $this->adapter = $this->freshAdapter();
        }

        return call_user_func_array([$this->adapter, $method], $args);
    }

    /**
     * Check if the current OpenStack authentication token is expired.
     *
     * @return boolean
     */
    protected function isTokenExpired()
    {
        if (is_null($this->expires)) {
            return true;
        }

        return (new DateTimeImmutable) >= $this->expires;
    }

    /**
     * Get a new Swift adapter instance with a refreshed authentication token.
     *
     * @return SwiftAdapter
     */
    protected function freshAdapter()
    {
        $this->params['cachedToken'] = $this->freshToken();
        $container = (new OpenStack($this->params))
            ->objectStoreV1()
            ->getContainer($this->container);

        return new SwiftAdapter($container);
    }

    /**
     * Get a fresh OpenStack authentication token.
     *
     * @return array
     */
    protected function freshToken()
    {
        $cachedTokenKey = "openstack-swift-token-{$this->params['user']['name']}-{$this->params['user']['domain']['name']}";

        // Cache the authentication token to significantly speed up requests.
        // See: http://php-openstack-sdk.readthedocs.io/en/identity-v2/services/identity/v3/tokens.html#cache-authentication-token
        if (Cache::has($cachedTokenKey)) {
            return Cache::get($cachedTokenKey);
        }

        $openstack = new OpenStack(['authUrl' => $this->params['authUrl']]);
        $token = $openstack->identityV3()->generateToken($this->params);
        $this->expires = $token->expires;
        $cachedToken = $token->export();
        // Convert DateTimeImmutable to DateTime because Cache::put expects
        // the latter to determine the expiration.
        $expires = new DateTime($token->expires->format('c'));
        Cache::put($cachedTokenKey, $cachedToken, $expires);

        return $cachedToken;
    }
}
