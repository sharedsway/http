<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-7
 * Time: 下午11:27
 */

namespace Sharedsway\Http;

use Sharedsway\Di\Di;
use Swoole;
use Sharedsway\Http\Http\MiddlewareArg;


class Application
{

    /**
     * 中间件
     * @var MiddlewareArg[]
     */
    protected $middleware = [];


    /**
     * 注册中间件
     * @param $middleware
     */
    public function use($middleware)
    {
        $fn  = @func_get_arg(0);
        $fn2 = @func_get_arg(1);


        $uri        = '*';
        $middleware = null;


        if (is_string($fn)) {
            $uri = $fn;
            if (is_callable($fn2)) {
                $middleware = $fn2;
            }
        } else {
            $middleware = $fn;
        }

        if (!is_callable($middleware)) {
            fwrite(STDOUT, sprintf('middleware used error,the middleware is not callable %s', PHP_EOL));
            return;
        }

        $this->middleware[] = new MiddlewareArg($uri, $middleware);
    }


    public function start()
    {

        $server = new Swoole\WebSocket\Server('0.0.0.0', 50010);

        $server->on('Start', [$this, 'onStart']);
        $server->on('ManagerStart', [$this, 'onManagerStart']);
        $server->on('WorkerStart', [$this, 'onWorkerStart']);
        $server->on('Request', [$this, 'onRequest']);
        $server->on('Message', [$this, 'onMessage']);
        $server->on('Open', [$this, 'onOpen']);

        $server->start();

    }


    /**
     * 主进程
     * @param Swoole\WebSocket\Server $server
     */
    public function onStart(Swoole\WebSocket\Server $server)
    {
        var_dump('主进程启动');
    }

    /**
     * 管理进程
     * @param Swoole\WebSocket\Server $server
     */
    public function onManagerStart(Swoole\WebSocket\Server $server)
    {
        var_dump('管理进程启动');
    }


    /**
     * 工作进程
     * @param Swoole\WebSocket\Server $server
     * @param int $workerId
     */
    public function onWorkerStart(Swoole\WebSocket\Server $server, int $workerId)
    {
        var_dump('工作进程启动#' . $workerId);
    }


    /**
     * @param Swoole\Http\Request $request
     * @param Swoole\Http\Response $response
     */
    public function onRequest(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {

        // init di ，后面用可以考虑事件管理器生成
        $di = new Di();
        $di->setShared('swooleRequest', $request);
        $di->setShared('swooleResponse', $response);

        // init context ，后面用可以考虑事件管理器生成
        $context = new Context();
        $context->setDI($di);
        $context->response = $response;
        $context->request  = $request;

        // init context ，后面用可以考虑事件管理器生成
        $http = new Http();
        $http->setContext($context);

        //
        foreach ($this->middleware as $middlewareArg) {
            $http->use($middlewareArg->uri, $middlewareArg->middleware);
        }


        $http->handle();
    }


    /**
     * @param Swoole\WebSocket\Server $server
     * @param Swoole\WebSocket\Frame $frame
     */
    public function onMessage(Swoole\WebSocket\Server $server, Swoole\WebSocket\Frame $frame)
    {

    }

    /**
     * @param $server
     * @param $request
     */
    public function onOpen(Swoole\WebSocket\Server $server, $request)
    {

    }

}