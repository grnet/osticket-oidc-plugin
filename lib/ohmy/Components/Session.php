<?php namespace ohmy\Components;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */


interface Session {

    /*
     * Create a new session.
     */
    public function create($key, $value);
    public function read($key);
    public function update($key, $value);
    public function delete($key);

} 