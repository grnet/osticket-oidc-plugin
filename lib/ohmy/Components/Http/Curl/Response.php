<?php namespace ohmy\Components\Http\Curl;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

use ohmy\Auth\Promise;

class Response extends Promise {

    public $headers = array();
    public $text;

    public function __construct($callback) {
        parent::__construct($callback);
        list($headers, $text) = explode("\r\n\r\n", $this->value, 2);
        if (strpos($headers, ' 100 Continue') !== false) {
            list($headers, $text) = explode("\r\n\r\n", $text, 2);
        }

        # parse the headers
        $headers = explode("\n", str_replace("\r", '', $headers));
        foreach ($headers as $header) {
            $header = explode(': ', $header);
            if (count($header) === 2) {
                $this->headers[$header[0]] = $header[1];
            }
        }
        $this->text = $text;
        $this->value = $this;
    }

    public function json() {
        return json_decode($this->text, true);
    }

    public function headers() {
        return $this->headers;
    }

    public function raw() {
        return $this->value;
    }

    public function text() {
        return $this->text;
    }

}

