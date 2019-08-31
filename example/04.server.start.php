<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-27
 * Time: 下午4:30
 */

require __DIR__ . '/../vendor/autoload.php';

use Sharedsway\Http\Application;

$server = new Application();

$server->use(function (\Sharedsway\Http\Context $context, $next) {

    echo '<pre>';

    var_dump('Is there the service named testHello in the di? ');
    var_dump(sprintf('The answer is %s', var_export($context->getDI()->has('testHello'), true)));

    var_dump('Is there the service named testHello2 in the di? ');
    var_dump(sprintf('The answer is %s', var_export($context->getDI()->has('testHello2'), true)));
    echo PHP_EOL;
    var_dump('What is the prop named processName in the context? ');
    var_dump(sprintf('The answer is %s', $context->processName ?? '__none__'));
    echo PHP_EOL;

    $next();
    echo PHP_EOL;
    echo 'finish';
    echo '</pre>';
});

$server->use(function ($context, $next) {
    $context->hello = 'world!!!';
    var_dump('start 1');
    $next();
    var_dump('end 1');
});

$server->use(function ($context, $next) {
    var_dump('start 2');
    $next();
    var_dump('end 2');
});

$server->use(function ($context, $next) {
    var_dump('start 3');
    $next();
    var_dump('end 3');
});

$server->use('/hello', function ($context, $next) {

    var_dump('before hello');
    $next();
    var_dump('after hello');

});
$server->use('/hello', function ($context, $next) {
    var_dump('the response is "hello hello"');
});

$server->use('/', function ($context, $next) {
    var_dump('before /');
    var_dump('hello ' . $context->hello);
    $next();
    var_dump('hello ' . $context->hello);
    var_dump('after /');
});

$server->use('/', function ($context, $next) {
    var_dump('the response of uri /');
    $context->hello = 'the gone-away ' . $context->hello;
});

$server->use(function ($context, $next) {
    var_dump('this is not visible');
});


try {

    $server->eventManager->attach('server:workerStart', function (\Sharedsway\Event\EventInterface $event, $source, $data) {
        var_dump(sprintf('工作进程已启动 #%d', $data->workerId));
        cli_set_process_title(sprintf('[%s]worker', 'sharedsway'));
    });

    /// 在这里加入事件
    /// 测试一下，从外部注入 di
    $server->eventManager->attach('http:initDi', function (\Sharedsway\Event\EventInterface $event, $source) {
        $di = new \Sharedsway\Di\Di();
        $di->setShared('testHello', function () {
            return new stdClass();
        });
        var_dump('this can be shown in terminal');
        return $di;
    });

    $server->eventManager->attach('http:initDi', function (\Sharedsway\Event\EventInterface $event, $source) {
        $di = new \Sharedsway\Di\Di();
        $di->setShared('testHello2', function () {
            return new stdClass();
        });
        return $di;
    });

    ///
    ///  同理，也能从外部注入context.
    $server->eventManager->attach('http:initContext', function (\Sharedsway\Event\EventInterface $event, $source, $di) {
        $context              = new \Sharedsway\Http\Context();
        $context->processName = 'Sharedsway';
        return $context;
    });

    /// 注册http
    $server->eventManager->attach('http:initHttp', function (\Sharedsway\Event\EventInterface $event, $source, $context) {
        $http              = new \Sharedsway\Http\Http();
        $http->use(function($context,$next){
            var_dump('this used in http injected');
            $next();

        });
        return $http;
    });

    $server->init()->start();
} catch (Exception $exception) {
    var_dump($exception->getTraceAsString());
}
