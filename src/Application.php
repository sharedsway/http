<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-7
 * Time: 下午11:27
 */

namespace Sharedsway\Sharedsway;

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
        ob_start();
        // ---- script start
        //浏览器请求的写这里就如同传统php的入口文件 index.php

        echo 'hello world!!!';


        echo '<pre>';
        echo '------ server ------';
        print_r($request->server ?? []);
        echo '------ get ------';
        print_r($request->get ?? []);
        echo '------ post ------';
        print_r($request->post ?? []);
        echo '</pre>';

        //输出到控制台，使用标准输出
        fwrite(STDOUT, "console.log(\"hello world\");\n");

        // ---- script end
        $content = ob_get_contents();
        ob_end_clean();
        $response->end($content);
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