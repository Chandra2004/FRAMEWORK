<?php
    use {{NAMESPACE}}\Helpers\Helper;

    if (!function_exists('url')) {
        function url($path = '') {
            return Helper::url($path);
        }
    }

    if (!function_exists('redirect')) {
        function redirect($url, $status = null, $message = null) {
            Helper::redirect($url, $status, $message);
        }
    }

    if (!function_exists('request')) {
        function request($key = null, $default = null) {
            return Helper::request($key, $default);
        }
    }

    if (!function_exists('set_flash')) {
        function set_flash($key, $message) {
            Helper::set_flash($key, $message);
        }
    }

    if (!function_exists('get_flash')) {
        function get_flash($key) {
            return Helper::get_flash($key);
        }
    }

    if (!function_exists('generateUUID')) {
        function generateUUID() {
            return Helper::generateUUID();
        }
    }
    
?>
