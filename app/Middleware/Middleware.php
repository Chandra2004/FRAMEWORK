<?php

namespace TheFramework\Middleware;

interface Middleware
{
    function before();
    function after();
}
