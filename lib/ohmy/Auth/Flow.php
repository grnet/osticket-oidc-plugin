<?php namespace ohmy\Auth;

/*
 * Copyright (c) 2014, Yahoo! Inc. All rights reserved.
 * Copyrights licensed under the New BSD License.
 * See the accompanying LICENSE file for terms.
 */

abstract class Flow extends Promise {

    public function set($key, $value=null) {
        (is_array($key)) ? $this->setArray($key) : $this->_set($key, $value);
        return $this;
    }

    public function setArray(Array $array) {
        foreach($array as $key => $value) {
            $this->_set($key, $value);
        }
    }

    public function override($component, $object) {
        switch($component) {
            case 'http':
                $this->client = $object;
                break;
            case 'session':
                $this->session = $object;
                break;
        }
        return $this;
    }

    public abstract function _set($key, $value);

} 
