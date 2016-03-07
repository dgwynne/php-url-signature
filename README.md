# php-url-signature

The URL Signature Authentication library for PHP implements a client
for Joyent's URL signature scheme as used by Manta.

## Usage

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
