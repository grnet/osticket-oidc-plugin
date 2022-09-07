<?php namespace ohmy\Auth1\Security;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

class Signature {

    private $method;
    private $url;
    private $params;
    private $oauth;
    private $type;
    private $oauth_consumer_secret;
    private $oauth_token_secret;
    private $debug = false;

    public function __construct(
        $method,
        $url,
        $oauth=array(),
        $params=array(),
        $headers=array()
    ) {

        $url = parse_url($url);
        $params = array_merge($oauth, $params);

        if (isset($url['query'])) parse_str($url['query'], $_params);
        else $_params = array();

        $params = array_merge($params, $_params);

        # set consumer/token secrets
        $this->oauth_consumer_secret = $params['oauth_consumer_secret'];
        $this->oauth_token_secret = (isset($params['oauth_token_secret'])) ? $params['oauth_token_secret'] : '';

        unset($params['oauth_consumer_secret']);
        unset($params['oauth_token_secret']);
        $oauth['oauth_signature'] = true;

        # sort params
        ksort($params);

        # constructor
        $this->method = $method;
        $this->url = $url['scheme'].'://'.$url['host'].$url['path'];
        $this->params = $params;
        $this->oauth = $oauth;
        $this->type = $params['oauth_signature_method'];

    }

    public function getSignature() {
        $base_string = $this->getBaseString();
        $signing_key = $this->getSigningKey();
        $oauth_signature = null;

        switch($this->type) {
            case 'PLAINTEXT':
                break;
            case 'HMAC-SHA1':
                $oauth_signature = base64_encode(hash_hmac(
                    'sha1',
                    $base_string,
                    $signing_key,
                    true
                ));
                break;
            case 'RSA-SHA1':
                break;
            default:
        }

        if ($this->debug) error_log("OAUTH_SIGNATURE: $oauth_signature");
        return $oauth_signature;
    }

    public function __toString() {

        $output = array();
        $params = $this->params;
        $params['oauth_signature'] = rawurlencode($this->getSignature());
        ksort($params);

        foreach($params as $key => $value) {
            if (isset($this->oauth[$key])) {
                if ($key == 'oauth_token') array_push($output, "$key=\"". rawurlencode($value) ."\"");
                else array_push($output, "$key=\"". $value ."\"");
            }
        }

        # sort($output);
        $output = 'OAuth '.implode(', ', $output);

        # return $oauth_signature;
        return $output;
    }

    public function getQueryString() {
        $output = array();
        foreach($this->params as $key => $value) {
            array_push($output, rawurlencode($key).'='.rawurlencode($value));
        }
        return implode('&', $output);
    }

    public function getBaseString() {

        $output =  $this->method
                   .'&'
                   .rawurlencode($this->url)
                   .'&'
                   .rawurlencode($this->getQueryString());

        if ($this->debug) error_log("SIGNATURE BASE STRING: $output");
        return $output;
    }

    public function getSigningKey() {
        $output =  $this->oauth_consumer_secret
                   .'&'
                   .$this->oauth_token_secret;

        if ($this->debug) error_log("SIGNING KEY: $output");
        return $output;
    }

}
