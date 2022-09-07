<?php namespace ohmy\Auth1\Flow;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth1\Auth1Flow,
    ohmy\Auth1\Security\Signature,
    ohmy\Auth1\Flow\TwoLegged\Request,
    ohmy\Auth1\Flow\TwoLegged\Access,
    ohmy\Components\Http;

class TwoLegged extends Auth1Flow {

    public $client;

    public function __construct($callback, Http $client=null) {
        parent::__construct($callback);
        $this->client = $client;
    }

    public function request($url, $options=null) {
        $self = $this;
        $request = new Request(function($resolve, $reject) use($self, $url, $options) {

            $signature = new Signature(
                'POST',
                $url,
                array_intersect_key(
                    $self->value,
                    array_flip(array(
                        'oauth_consumer_key',
                        'oauth_consumer_secret',
                        'oauth_timestamp',
                        'oauth_nonce',
                        'oauth_signature_method',
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

        return $request->then(function($data) use($self) {
            parse_str($data, $array);
            return array_merge($self->value, $array);
        });
    }

    public function access($token, $secret) {
        $self = $this;
        $this->value['oauth_token'] = $token;
        $this->value['oauth_token_secret'] = $secret;
        return new Access(function($resolve) use($self) {
            $resolve($self->value);
        }, $this->client);
    }

}
