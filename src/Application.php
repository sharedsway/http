<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-7
 * Time: 下午11:27
 */

namespace Sharedsway\Http;

use Swoole;


class Application
{


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

        $http = new Http($request, $response);

        $http->use(function ($context, $next) {
            $context->hello = 'world';
            var_dump('start 1');
            $next();
            var_dump('end 1');
        });

        $http->use(function ($context, $next) {
            var_dump('start 2');
            $next();
            var_dump('end 2');
        });

        $http->use(function ($context, $next) {
            var_dump('start 3');
            $next();
            var_dump('end 3');
        });

        $http->use('/hello', function ($context, $next) {
            var_dump('the response is "hello hello"');
        });

        $http->use(function ($context, $next) {
            var_dump('hello ' . $context->hello);
        });

        $http->use(function ($context, $next) {
            var_dump('this is not visible');
        });

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