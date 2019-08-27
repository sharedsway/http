<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-27
 * Time: 下午4:30
 */

require __DIR__ . '/../vendor/autoload.php';

use Sharedsway\Http\Application;

$server  = new Application();

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
    var_dump('the response is "hello hello"');
});

$server->use('/',function ($context, $next) {
    var_dump('hello ' . $context->hello);
});

$server->use(function ($context, $next) {
    var_dump('this is not visible');
});

$server->start();