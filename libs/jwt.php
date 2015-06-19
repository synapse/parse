<?php

/**
 * @package     Synapse
 * @subpackage  JWT
 * @original Neuman Vong https://github.com/firebase/php-jwt
 */

defined('_INIT') or die;

class JWT extends Object
{
    public $methods = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
    );

    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string      $jwt       The JWT
     * @param string|Array|null $key The secret key, or map of keys
     * @param bool        $verify    Don't skip verification process
     *
     * @return object|False      The JWT's payload as a PHP object
     *
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    public function decode($jwt, $key = null, $verify = true)
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            $this->setError( __('Wrong number of segments') );
            return false;
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;

        $header = $this->jsonDecode($this->urlsafeB64Decode($headb64));
        if (null === $header || false === $header) {
            $this->setError( __('Invalid header encoding') );
            return false;
        }

        $payload = $this->jsonDecode($this->urlsafeB64Decode($bodyb64));
        if (null === $payload || false === $payload) {
            $this->setError( __('Invalid claims encoding') );
            return false;
        }

        $sig = $this->urlsafeB64Decode($cryptob64);
        if ($verify) {
            if (empty($header->alg)) {
                $this->setError( __('Empty algorithm') );
                return false;

            }
            if (is_array($key)) {
                if (isset($header->kid)) {
                    $key = $key[$header->kid];
                } else {
                    $this->setError( __('"kid" empty, unable to lookup correct key') );
                    return false;
                }
            }

            // Check the signature
            if (!$this->verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
                $this->setError( __('Signature verification failed') );
                return false;
            }

            // Check if the nbf if it is defined. This is the time that the
            // token can actually be used. If it's not yet that time, abort.
            if (isset($payload->nbf) && $payload->nbf > time()) {
                $this->setError( __('Cannot handle token prior to {1}', date(DateTime::ISO8601, $payload->nbf)) );
                return false;
            }

            // Check that this token has been created before 'now'. This prevents
            // using tokens that have been created for later use (and haven't
            // correctly used the nbf claim).
            if (isset($payload->iat) && $payload->iat > time()) {
                $this->setError( __('Cannot handle token prior to {1}', date(DateTime::ISO8601, $payload->iat)) );
                return false;
            }

            // Check if this token has expired.
            if (isset($payload->exp) && time() >= $payload->exp) {
                $this->setError( __('Expired token') );
                return false;
            }
        }

        return $payload;
    }

    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload PHP object or array
     * @param string       $key     The secret key
     * @param string       $algo    The signing algorithm. Supported
     *                              algorithms are 'HS256', 'HS384' and 'HS512'
     *
     * @return string      A signed JWT
     * @uses jsonEncode
     * @uses urlsafeB64Encode
     */
    public function encode($payload, $key, $algo = 'HS256', $keyId = null)
    {
        $header = array('typ' => 'JWT', 'alg' => $algo);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        $segments = array();

        $encodedHeader = $this->jsonEncode($header);
        if($encodedHeader === false) return false;
        $segments[] = $this->urlsafeB64Encode($encodedHeader);

        $encodedPayload = $this->jsonEncode($payload);
        if($encodedPayload === false) return false;
        $segments[] = $this->urlsafeB64Encode($encodedPayload);

        $signing_input = implode('.', $segments);

        $signature = $this->sign($signing_input, $key, $algo);
        if($signature === false) return false;

        $segments[] = $this->urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg          The message to sign
     * @param string|resource $key The secret key
     * @param string $method       The signing algorithm. Supported algorithms
     *                               are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return string|false          An encrypted message
     */
    public function sign($msg, $key, $method = 'HS256')
    {
        if (empty($this->methods[$method])) {
            $this->setError( __('Algorithm not supported') );
            return false;
        }

        list($function, $algo) = $this->methods[$method];
        switch($function) {
            case 'hash_hmac':
                return hash_hmac($algo, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $key, $algo);
                if (!$success) {
                    throw new DomainException("OpenSSL unable to sign data");
                } else {
                    return $signature;
                }
        }
    }

    /**
     * Verify a signature with the mesage, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     * @param string $msg the original message
     * @param string $signature
     * @param string|resource $key for HS*, a string key works. for RS*, must be a resource of an openssl public key
     * @param string $method
     * @return bool|false
     */
    public function verify($msg, $signature, $key, $method = 'HS256')
    {
        if (empty($this->methods[$method])) {
            $this->setError( __('Algorithm not supported') );
            return false;
        }

        list($function, $algo) = $this->methods[$method];
        switch($function) {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algo);
                if (!$success) {
                    $this->setError( __('OpenSSL unable to verify data: {1}', openssl_error_string()) );
                    return false;
                } else {
                    return $signature;
                }
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algo, $msg, $key, true);
                $len = min(strlen($signature), strlen($hash));

                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (strlen($signature) ^ strlen($hash));

                return ($status === 0);
        }
    }

    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object          Object representation of JSON string
     * @throws DomainException Provided string was invalid JSON
     */
    public function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            /** Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             *them to strings) before decoding, hence the preg_replace() call.
             */
            $max_int_length = strlen((string) PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{'.$max_int_length.',})/', ': "$1"', $input);
            $obj = json_decode($json_without_bigints);
        }

        if (function_exists('json_last_error') && $errno = json_last_error()) {
            $this->handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            $this->setError( __('Null result with non-null input') );
            return false;
        }
        return $obj;
    }

    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string          JSON representation of the PHP object or array
     * @throws DomainException Provided object could not be encoded to valid JSON
     */
    public function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            return $this->handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            $this->setError( __('Null result with non-null input') );
            return false;
        }
        return $json;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    public function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    public function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Helper method to create a JSON error.
     *
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private function handleJsonError($errno)
    {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
        );

        $this->setError( isset($messages[$errno]) ? $messages[$errno] : __('Unknown JSON error: {1}', $errno)  );
        return false;

    }
}
