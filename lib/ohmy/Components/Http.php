<?php namespace ohmy\Components;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

interface Http {

    public function POST($url, Array $arguments, Array $headers);
    public function GET($url, Array $arguments, Array $headers);

} 