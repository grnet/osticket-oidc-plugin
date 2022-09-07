<?php namespace ohmy\Auth2\Flow;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth2\Auth2Flow,
    ohmy\Auth2\Flow\ThreeLegged\Authorize,
    ohmy\Auth2\Flow\ThreeLegged\Access;

class ThreeLegged extends Auth2Flow {

    private $client;

    public function __construct($callback, $client=null) {
        parent::__construct($callback);
        $this->client = $client;
    }

    public function authorize($url, Array $options=array()) {
        $self = $this;
        return new Authorize(function($resolve, $reject) use($self, $url, $options) {

            if($self->value['code']) {
                $resolve($self->value);
                return;
            }

            $location = $url.'?'.http_build_query(array(
                'response_type' => 'code',
                'client_id'     => $self->value['client_id'],
                'redirect_uri'  => $self->value['redirect_uri'],
                'scope'         => $self->value['scope'],
                'state'         => $self->value['state']

            ));

            header("Location: $location");
            exit();

        }, $this->client);
    }

    public function access($token) {
        $self = $this;
        $this->value['access_token'];
        return new Access(function($resolve) use($self) {
            $resolve($self->value);
        }, $this->client);
    }
}
