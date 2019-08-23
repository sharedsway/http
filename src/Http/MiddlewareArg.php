<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-21
 * Time: 下午7:15
 */

namespace Sharedsway\Http\Http;
class MiddlewareArg
{
    public $middleware;

    public $uri = '*';

    public function __construct($uri, $middleware)
    {
        $this->middleware = $middleware;

        $this->uri = $uri;
    }
}