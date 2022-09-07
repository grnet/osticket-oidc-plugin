<?php namespace ohmy\Auth1\Flow;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth1\Auth1Flow,
    ohmy\Auth1\Security\Signature,
    ohmy\Auth1\Flow\ThreeLegged\Request,
    ohmy\Auth1\Flow\ThreeLegged\Access,
    ohmy\Components\Http,
    ohmy\Components\Session;

class ThreeLegged extends Auth1Flow {

    public $client;

    public function __construct($callback, Http $client=null, Session $session=null) {
        parent::__construct($callback);
        $this->client = $client;
        $this->session = $session;
    }

    public function request($url, $options=array()) {
        $self = $this;
        $request = new Request(function($resolve, $reject) use($self, $url, $options) {
        

            if (!empty($self->value['oauth_token'])) {
                $resolve($self->value);
                return;
            }

            $signature = new Signature(
                'POST',
                $url,
                array_intersect_key(
                    $self->value,
                    array_flip(array(
                        'oauth_callback',
                        'oauth_consumer_key',
                        'oauth_consumer_secret',
                        'oauth_nonce',
                        'oauth_timestamp',
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
            if (is_array($data)) return $data;
            parse_str($data, $array);
            $self->session->create('oauth_token_secret', $array['oauth_token_secret']);
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
