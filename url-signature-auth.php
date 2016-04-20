<?php

class URLSignatureResult {
	private $_error;
	private $_value;

	public function __construct($e, $v) {
		$this->_error = $e;
		$this->_value = $v;
	}

	public function error() {
		return ($this->_error);
	}
	public function errmsg() {
		return ($this->_value);
	}

	public function value() {
		return ($this->_value);
	}
}

class URLSignature {
	static private function result($e, $v) {
		return new URLSignatureResult($e, $v);
	}
	static private function err($msg) {
		return self::result(E_WARNING, $msg);
	}
	static private function ok($url) {
		return self::result(0, $url);
	}

	static protected function rsa_sign($data, &$signature, $key,
	    $algorithm = OPENSSL_ALGO_SHA1) {
		$k = openssl_pkey_get_private($key);
		if ($k === false) {
			return (false);
		}

		return openssl_sign($data, $signature, $k, $algorithm);
	}

	static public function sign($url, $keyId, $key, array $options = []) {
		if (is_string($url)) {
			$url = parse_url($url);
			if ($url === false) {
				return self::err("unable to parse url");
			}
		}

		if (!isset($url['host'])) {
			return self::err("missing host in url");
		}
		if (!isset($url['path'])) {
			return self::err("missing path in url");
		}

		$method = isset($options['method']) ?
		    $options['method'] : 'GET';

		$offset = isset($options['offset']) ?
		    $options['offset'] : 3600;

		$expires = isset($options['expires']) ?
		    $options['expires'] : time() + $offset;

		$algorithm = isset($options['algorithm']) ?
		    $options['algorithm'] : 'rsa-sha512';

		$rsa_sign = isset($options['rsa_sign']) ?
		    $options['rsa_sign'] : [ get_class($this), 'rsa_sign' ];

		$parts = explode('-', strtolower($algorithm));
		if (sizeof($parts) != 2) {
			return self::err("invalid algorithm");
		}
		list($type, $hash) = $parts;

		$get = [];
		if (isset($url['query'])) {
			parse_str($url['query'], $get);
		}

		$get['algorithm'] = $algorithm;
		$get['keyId'] = $keyId;
		$get['expires'] = $expires;

		ksort($get);
		$query = http_build_query($get);

		$data = implode("\n", [
			$method,
			$url['host'],
			$url['path'],
			$query,
		]);

		switch ($type) {
		case 'rsa':
			if ($rsa_sign($data, $signature,
			    $key, $hash) === false) {
				return self::err("unable to sign request");
			}
			break;

		case 'hash':
			$signature = hash_hmac($hash, $data, $key, true);
			if ($signature === false) {
				return self::err("unsupported algorithm");
			}
			break;

		default:
			return self::err("unsupported algorithm");
		}

		$url['query'] = $query .
		    '&signature=' . urlencode(base64_encode($signature));

		return self::ok($url);
	}
}
