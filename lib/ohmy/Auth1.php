<?php namespace ohmy;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth1\Flow\TwoLegged,
    ohmy\Auth1\Flow\ThreeLegged,
    ohmy\Components\Http\Curl\Request,
    ohmy\Components\Session\PHPSession;

class Auth1 {

    public static function legs($num) {
        return self::init($num);
    }

    public static function init($type) {

        $client = new Request;
        $oauth = array(
            'oauth_callback'           => '',
            'oauth_consumer_key'       => '',
            'oauth_consumer_secret'    => '',
            'oauth_nonce'              => md5(mt_rand()),
            'oauth_signature_method'   => 'HMAC-SHA1',
            'oauth_timestamp'          => time(),
            'oauth_version'            => '1.0',
            'oauth_token'              => isset($_REQUEST['oauth_token']) ? $_REQUEST['oauth_token'] : '',
            'oauth_verifier'           => isset($_REQUEST['oauth_verifier']) ? $_REQUEST['oauth_verifier'] : ''
        );

        # encode all params
        foreach($oauth as $key => $val) $oauth[$key] = rawurlencode($val);

        switch($type) {
            case 2:
                return new TwoLegged(function($resolve) use($oauth) {
                    $resolve($oauth);
                }, $client);
                break;
            case 3:
                $session = new PHPSession;
                $oauth['oauth_token_secret'] = $session->read('oauth_token_secret');
                return new ThreeLegged(function($resolve) use($oauth) {
                    $resolve($oauth);
                }, $client, $session);
                break;
            default:
        }
    }
}
