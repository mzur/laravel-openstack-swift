<?php

namespace Mzur\Filesystem;

use OpenStack\ObjectStore\v1\Models\Container;
use Nimbusoft\Flysystem\OpenStack\SwiftAdapter as BaseAdapter;

class SwiftAdapter extends BaseAdapter
{
    /**
     * Optional base URL to use for this adapter.
     *
     * @var string
     */
    protected $url;

    /**
     * Constructor
     *
     * @param Container $container
     * @param string    $prefix
     * @param string $url
     */
    public function __construct(Container $container, $prefix = '', $url = '')
    {
        parent::__construct($container, $prefix);
        $this->url = rtrim($url, '/');
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function getUrl($path)
    {
        $path = $this->prefixer->prefixPath($path);

        if ($this->url) {
            return "{$this->url}/{$path}";
        }

        return (string) $this->container->getObject($path)->getPublicUri();
    }
}
