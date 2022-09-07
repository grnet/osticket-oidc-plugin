<?php namespace ohmy\Auth1\Flow\TwoLegged;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth\Promise,
    ohmy\Components\Http,
    ohmy\Auth1\Security\Signature;

class Access extends Promise {

    public function __construct($callback, Http $client=null) {
        parent::__construct($callback);
        $this->client = $client;
    }

    public function GET($url, $params=array(), $headers=array()) {
        $url = parse_url($url);
        if (isset($url['query'])) parse_str($url['query'], $params);
        return $this->request(
            'GET', 
            $url['scheme'].'://'.$url['host'].$url['path'],
            $params,
            $headers
        );
    }

    public function POST($url, $params=array(), $headers=array()) {
        $url = parse_url($url);
        if (isset($url['query'])) parse_str($url['query'], $params);
        return $this->request(
            'POST',
            $url['scheme'].'://'.$url['host'].$url['path'],
            $params,
            $headers
        );
    }

    private function request($method, $url, $params=array(), $headers=array()) {

        # sign request
        $signature = new Signature(
            $method,
            $url,
            array_intersect_key(
                $this->value,
                array_flip(array(
                    'oauth_consumer_key',
                    'oauth_consumer_secret',
                    'oauth_nonce',
                    'oauth_signature_method',
                    'oauth_timestamp',
                    'oauth_token',
                    'oauth_token_secret',
                    'oauth_version'
                ))
            ),
            $params,
            $headers
        );

        # set Authorization header
        $headers['Authorization'] = $signature;

        return $this->client->{$method}($url, $params, $headers);
    }
}
