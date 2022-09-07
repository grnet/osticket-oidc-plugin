<?php namespace ohmy\Auth1\Flow\ThreeLegged;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth\Promise,
    ohmy\Components\Http,
    ohmy\Components\Session;

class Request extends Promise {

    public function __construct($callback, Http $client=null, Session $session=null) {
        parent::__construct($callback);
        $this->client = $client;
        $this->session = $session;
    }

    public function authorize($url, $options=array()) {
        $self = $this;
        return (new Authorize(function($resolve, $reject) use($self, $url, $options) {

            # check session
            if ($self->value['oauth_token'] && $self->value['oauth_verifier']) {
                $resolve($self->value);
                return;
            }

            $location = sprintf(
                'Location: %s?oauth_token=%s',
                $url,
                $self->value['oauth_token']
            );

            header($location);
            exit();
        }, $this->client));

    }
}
