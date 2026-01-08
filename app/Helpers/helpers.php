<?php

use TheFramework\Helpers\Helper;

if (!function_exists('url')) {
    function url($path = '')
    {
        return Helper::url($path);
    }
}

if (!function_exists('redirect')) {
    function redirect($url, $status = null, $message = null)
    {
        Helper::redirect($url, $status, $message);
    }
}

if (!function_exists('request')) {
    function request($key = null, $default = null)
    {
        return Helper::request($key, $default);
    }
}

if (!function_exists('set_flash')) {
    function set_flash($key, $message)
    {
        Helper::set_flash($key, $message);
    }
}

if (!function_exists('get_flash')) {
    function get_flash($key)
    {
        return Helper::get_flash($key);
    }
}

if (!function_exists('uuid')) {
    function uuid(int $length = 36)
    {
        return Helper::uuid($length);
    }
}

if (!function_exists('updateAt')) {
    function updateAt()
    {
        return Helper::updateAt();
    }
}

if (!function_exists('rupiah')) {
    function rupiah($number)
    {
        return Helper::rupiah($number);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        return Helper::generateCsrfToken();
    }
}

if (!function_exists('e')) {
    function e($value)
    {
        return Helper::e($value);
    }
}

if (!function_exists('old')) {
    function old($field, $default = null)
    {
        return Helper::old($field, $default);
    }
}

if (!function_exists('error')) {
    function error($field)
    {
        return Helper::validation_errors($field);
    }
}

if (!function_exists('has_error')) {
    function has_error($field)
    {
        return Helper::has_error($field);
    }
}

if (!function_exists('dispatch')) {
    function dispatch($job, $queue = 'default')
    {
        return \TheFramework\App\Queue::push($job, [], $queue);
    }
}
