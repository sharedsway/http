<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-7
 * Time: 下午11:27
 */

namespace Sharedsway\Http;

use Sharedsway\Common\Exception;
use Sharedsway\Di\Di;
use Sharedsway\Di\DiInterface;
use Sharedsway\Di\Library\InjectableAwareTrait;
use Sharedsway\Di\Library\InjectionAwareInterface;
use Sharedsway\Event\Manager;
use Sharedsway\Event\ManagerInterface;
use Swoole;
use Sharedsway\Http\Http\MiddlewareArg;


/**
 * Class Application
 * @package Sharedsway\Http
 * @property Manager $eventManager
 */
class Application implements InjectionAwareInterface
{

    use InjectableAwareTrait{
        InjectableAwareTrait::__get as private __getTrait;
    }

    /**
     * 中间件
     * @var MiddlewareArg[]
     */
    protected $middleware = [];

    /**
     * @var Manager
     */
    protected $eventManager ;

    public function __construct()
    {

        $this->eventManager = new Manager();

    }

    /**
     * @return Manager
     */
    public function getEventManager(): Manager
    {
        return $this->eventManager;
    }

    /**
     *
     */
    protected function initDefault()
    {
        $this->setDI(new Di());

    }


    /**
     * @return $this
     * @throws Exception
     */
    public function init()
    {

        $listenerType = 'server:init';
        if ($this->eventManager->hasListeners($listenerType)) {
            try {
                $this->eventManager->fire($listenerType,$this);
            } catch (\Sharedsway\Event\Exception $exception) {
                throw new Exception($exception->getMessage());
            }
        }else{
            $this->initDefault();
        }

        //
        if (!$this->getDI() || !($this->getDI()  instanceof DiInterface)) {
            throw new Exception('invalid di');
        }

        $this->getDI()->setShared('eventManager', $this->eventManager);

        return $this;
    }


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

        $this->getDI()->setShared('swooleWebsocket', $server);

        $server->start();

    }


    /**
     * 主进程
     * @param Swoole\WebSocket\Server $server
     * @throws \Sharedsway\Event\Exception
     */
    public function onStart(Swoole\WebSocket\Server $server)
    {
        $this->eventManager->fire('server:start', $this, $server);
    }

    /**
     * 管理进程
     * @param Swoole\WebSocket\Server $server
     * @throws \Sharedsway\Event\Exception
     */
    public function onManagerStart(Swoole\WebSocket\Server $server)
    {
        $this->eventManager->fire('server:managerStart', $this, $server);
    }


    /**$source
     * 工作进程
     * @param Swoole\WebSocket\Server $server
     * @param int $workerId
     * @throws \Sharedsway\Event\Exception
     */
    public function onWorkerStart(Swoole\WebSocket\Server $server, int $workerId)
    {
        $data           = new \stdClass();
        $data->server   = $server;
        $data->workerId = $workerId;


        $this->eventManager->fire('server:workerStart', $this, $data);

        //这里还可以区分出工作进程和taskWorker
        if ($server->taskworker) {
            $this->eventManager->fire('server:taskWorkerStart', $this, $data);
        }else{
            //
            $this->eventManager->fire('server:workWorkerStart', $this, $data);
        }
    }


    /**
     * @return mixed|null|Di
     * @throws Exception
     * @throws \Sharedsway\Event\Exception
     */
    protected function requestInitDi()
    {
        $di = $this->eventManager->fire('http:initDi', $this);
        if (!$di) {
            $di = new Di();
        }

        if (!($di instanceof DiInterface)) {
            throw new Exception('invalid di');
        }

        if (!$di->has('eventManager')) {
            $di->setShared('eventManager', function () {
                $manager = new Manager();

                return $manager;
            });
        }

        return $di;
    }


    /**
     * @param DiInterface $di
     * @return mixed|null|Context
     * @throws Exception
     * @throws \Sharedsway\Event\Exception
     */
    protected function requestInitContext(DiInterface $di)
    {
        $context = $this->eventManager->fire('http:initContext', $this, $di);

        if (!$context) {
            $context = new Context();
        }

        if (!($context instanceof Context)) {
            throw new Exception('invalid context');
        }

        $context->setDI($di);

        return $context;
    }

    /**
     * @param Context $context
     * @return mixed|null|Http
     * @throws Exception
     * @throws \Sharedsway\Event\Exception
     */
    protected function requestInitHttp(Context $context)
    {
        $http = $this->eventManager->fire('http:initHttp', $this, $context);

        if (!$http) {
            $http = new Http();
        }

        if (!($http instanceof HttpInterface)) {
            throw new Exception('invalid http');
        }

        $http->setContext($context);

        return $http;
    }

    /**
     * @param Swoole\Http\Request $request
     * @param Swoole\Http\Response $response
     * @throws Exception
     * @throws \Sharedsway\Event\Exception
     */
    public function onRequest(Swoole\Http\Request $request, Swoole\Http\Response $response)
    {


        $di = $this->requestInitDi();
        $di->setShared('swooleRequest', $request);
        $di->setShared('swooleResponse', $response);
        /** @var Manager $eventManager */
        $eventManager = $di->getShared('eventManager');


        $context           = $this->requestInitContext($di);
        $context->response = $response;
        $context->request  = $request;


        $http = $this->requestInitHttp($context);


        $requestStartParams = new \stdClass();
        $requestStartParams->http = $http;
        $requestStartParams->context = $context;


        ///进程内事件
        $this->eventManager->fire('request:start', $this, $requestStartParams);

        /// http 内部事件，这够理应写在 http内部吧
        $eventManager->fire('request:start', $http, $requestStartParams);


        $this->eventManager->fire('request:beforeInjectMiddleware', $this, $requestStartParams);
        // 注入中间件
        foreach ($this->middleware as $middlewareArg) {
            $http->use($middlewareArg->uri, $middlewareArg->middleware);
        }
        $this->eventManager->fire('request:afterInjectMiddleware', $this, $requestStartParams);

        $http->handle();

        /// http 内部事件，这够理应写在 http内部吧
        $eventManager->fire('request:end', $http, $requestStartParams);
        $this->eventManager->fire('request:end', $this, $requestStartParams);
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

    /**
     *
     * 这么做只是为了兼容 InjectableAwareTrait 的写法，没有多大必要，要 eventManager 可以直接调 getEventManager
     *
     * @param string $propertyName
     * @return mixed|null|DiInterface|Manager
     * @throws \Sharedsway\Di\Exception
     */
    public function __get(string $propertyName)
    {

        if ('eventManager' == $propertyName) {
            return $this->eventManager;
        }

        $value = $this->__getTrait($propertyName);

        return $value;
    }

}