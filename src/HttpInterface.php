<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-11
 * Time: 上午12:08
 */

namespace Sharedsway\Http;


interface HttpInterface
{



    /**
     * 注册中间件
     * @param $middleware
     */
    public function use($middleware);





    /**
     * 进入下一个中间件
     */
    public function next();



    /**
     *
     */
    public function handle();
}