<?php

namespace Mzur\Filesystem;

use Illuminate\Support\Arr;
use OpenStack\ObjectStore\v1\Models\Container;

class TempUrlSwiftAdapter extends SwiftAdapter
{
    /**
     * Key for temporary URLs.
     *
     * @var string
     */
    protected $tempUrlKey;

    /**
     * Constructor
     *
     * @param Container $container
     * @param string $key
     * @param string    $prefix
     * @param string $url
     */
    public function __construct(Container $container, $key, $prefix = '', $url = '')
    {
        parent::__construct($container, $prefix, $url);
        $this->tempUrlKey = $key;
    }

    /**
     * Get the temporary URL for the file at the given path.
     *
     * see: https://docs.openstack.org/swift/latest/middleware.html#tempurl
     *
     * @param  string  $path
     * @param \DateTime $expiration
     * @param array $options
     * @return string
     */
    public function getTemporaryUrl($path, $expiration, $options)
    {
        // Reference for full implementation:
        // https://github.com/openstack/python-swiftclient/blob/master/swiftclient/utils.py#L74-L200
        // TODO Missing features:
        // - prefix
        // - IP range
        // - filename
        // - inline

        $expires = $expiration->getTimestamp();
        $url = $this->getUrl($path);
        $hmacPath = explode('v1', $url);
        array_shift($hmacPath);
        // Decode URL for signature only as stated by the documentation.
        $hmacPath = rawurldecode(implode('v1', $hmacPath));
        $hmacBody = "GET\n{$expires}\n/v1{$hmacPath}";
        $algo = Arr::get($options, 'algo', 'sha1');
        $sig = hash_hmac($algo, $hmacBody, $this->tempUrlKey);

        return "{$url}?temp_url_sig={$sig}&temp_url_expires={$expires}";
    }
}
