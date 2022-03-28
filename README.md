# Laravel OpenStack Swift

OpenStack Swift storage driver for Laravel/Lumen.

## Installation

Require the package with Composer:

```
composer require mzur/laravel-openstack-swift
```

### Laravel

This package is auto-discovered.

### Lumen

Add the service provider to `bootstrap/app.php`:
```php
$app->register(Mzur\Filesystem\SwiftServiceProvider::class);
```

## Configuration

Add a new storage disk to `config/filesystems.php` (using v3 of the identity API):

```php
'disks' => [
   'openstack' => [
      'driver'    => 'swift',
      'authUrl'   => env('OS_AUTH_URL', ''),
      'region'    => env('OS_REGION_NAME', ''),
      'user'      => env('OS_USERNAME', ''),
      'domain'    => env('OS_USER_DOMAIN_NAME', 'default'),
      'password'  => env('OS_PASSWORD', ''),
      'container' => env('OS_CONTAINER_NAME', ''),
   ],
]
```

Additional configuration options:

- `projectId` (default: `null`) if you want to scope access to a specific project

- `debugLog` (default: `false`), `logger` (default: `null`), `messageFormatter` (default: `null`) [[ref]](https://github.com/php-opencloud/openstack/issues/47#issuecomment-208181121)

- `requestOptions` (default: `[]`) [[ref]](https://github.com/php-opencloud/openstack/pull/63#issue-74731062)

- `swiftLargeObjectThreshold` [[ref]](https://github.com/mzur/flysystem-openstack-swift#configuration)

- `swiftSegmentSize` [[ref]](https://github.com/mzur/flysystem-openstack-swift#configuration)

- `swiftSegmentContainer` [[ref]](https://github.com/mzur/flysystem-openstack-swift#configuration)

- `root` (default: `null`): Prefix to use for the names of the objects in the container.

- `url` (default: `null`): Override URL to use for public URLs to objects. If this is not set, the public URL will point to the public URL of Swift. This configuration is useful if you use a reverse proxy to pass through requests to public Swift containers.

- `tempUrlKey`: The account or container level key for [temporary URLs](https://docs.openstack.org/swift/latest/api/temporary_url_middleware.html). If set, support for [temporary URLs](https://laravel.com/docs/master/filesystem#temporary-urls) is automatically enabled for the storage disk.

- `ttl`: Override the duration the OpenStack authentication token should be cached (in seconds). Values that are longer than the `expires_at` of the token are ignored.
