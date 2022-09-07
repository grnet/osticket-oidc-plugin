<?php namespace ohmy\Auth2\Flow\ThreeLegged;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth\Promise,
    ohmy\Components\Http;

class Authorize extends Promise {

    public $client;

    public function __construct($callback, Http $client=null) {
        parent::__construct($callback);
        $this->client = $client;
    }

    public function access($url, Array $options=array()) {
        $self = $this;
        $access = new Access(function($resolve, $reject) use($self, $url, $options) {
            $self->client->POST($url, array(
                'grant_type'    => 'authorization_code',
                'client_id'     => $self->value['client_id'],
                'client_secret' => $self->value['client_secret'],
                'code'          => $self->value['code'],
                'redirect_uri'  => $self->value['redirect_uri']
            ))
            ->then(function($response) use($resolve) {
                $resolve($response->text());
            });

        }, $this->client);

        return $access->then(function($data) use($self) {
            $value = null;
            parse_str($data, $array);
            if (count($array) === 1) {
                $json = json_decode($data, true);
                if ($json) $value = array_merge($self->value, $json);
                else $value['response'] = $data;
            }
            else $value =  array_merge($self->value, $array);
            return $value;
        });
    }
}
