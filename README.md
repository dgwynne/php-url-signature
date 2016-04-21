# php-url-signature

The URL Signature Authentication library for PHP implements a client
for Joyent's URL signature scheme, as used by Manta.

The library provides a URLSignature class containing the signature
functionality. The following methods are provided:

## `URLSignature::sign()`

~~~{.php}
$result = URLSignature::sign($url, $keyId, $key, array $options = [])
~~~

`sign()` signs a URL for a future request.

The URL to be signed is specified via the `$url` argument. It may
be passed as a array like what is returned by `parse_url()`, or as
a string.

The key to be used for signing the URL is passed as `$key`, and
identified by the `$keyId` parameter.

Optional parameters may be passed via the `$options` array. The
following options may be passed:

The algorithm may be specified via `$options['algorithm']`. Unless
specified, `'rsa-sha512'` is used.

The HTTP method may be specified via `$options['method']`. Unless
specified, `'GET'` is used.

The expiry for the signature may be specified as either an offset
from the current systems time using `$options['offset']`, or as an
abolute UNIX epoch time using `$options['expires']`. The default
value for the expiry is one hour in the future.

An alternative implementation of an RSA sign function may be provided
via `$options['rsa_sign']`. The specified function should implement
the same API as (`openssl_sign()`)[http://php.net/openssl_sign].
Unless specified, `openssl_sign()` is used.

### Return Value

`sign()` returns a URLSignatureResult object. This object provides
the following methods:

`$result->error()` returns a non-zero value if the signature
operation did not complete successfully.

If the result is an error, `$result->errmsg()` will return a string
describing the failure.

On success, `$result->value()` will return an array structured like
the result of `parse_url()`. This result may be used to build a URL
that can be fetched.

## Examples

### Client

```php
require_once('http-signature-auth.php');

$result = URLSignature::sign($url, $keyId, $key);
if ($result->error()) {
	trigger_error($result->errmsg(), E_USER_ERROR);
}

/* an array like what parse_url returns */
$parts = $result->value();

/* Http\Url from pecl-http is better */
$url = sprintf("%s://%s%s?%s", $parts['scheme'], $parts['host'],
    $parts['path'], $parts['query']);

// It's funny how much PHP sucks at HTTP things
$ch = curl_init($url);
curl_exec($ch);
curl_close($ch);
```
