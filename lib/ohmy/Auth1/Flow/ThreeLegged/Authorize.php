<?php namespace ohmy\Auth1\Flow\ThreeLegged;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth\Promise,
    ohmy\Components\Http,
    ohmy\Auth1\Security\Signature;

class Authorize extends Promise {

    public $client;

    public function __construct($callback, Http $client=null) {
        parent::__construct($callback);
        $this->client = $client;
    }

    public function access($url, $options=array()) {
        $self = $this;
        $access = new Access(function($resolve, $reject) use($self, $url, $options) {

            $signature = new Signature(
                'POST',
                $url,
                array_intersect_key(
                    $self->value,
                    array_flip(array(
                        'oauth_consumer_key',
                        'oauth_consumer_secret',
                        'oauth_nonce',
                        'oauth_signature_method',
                        'oauth_timestamp',
                        'oauth_token',
                        'oauth_token_secret',
                        'oauth_verifier',
                        'oauth_version'
                    ))
                )
            );

            $self->client->POST($url, array(), array(
                'Authorization'  => $signature,
                'Content-Length' => 0
            ))
            ->then(function($response) use($resolve) {
                $resolve($response->text());
            });

        }, $this->client);

        return $access->then(function($data) use($self) {
            parse_str($data, $array);
            return array_merge($self->value, $array);
        });
    }
}
