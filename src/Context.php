<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-10
 * Time: 下午11:42
 */
namespace Sharedsway\Http;
use Sharedsway\Di\Library\InjectableAwareTrait;
use Sharedsway\Di\Library\InjectionAwareInterface;


/**
 * Class Context
 * @package Sharedsway\Http
 * @property $request
 * @property $response
 *
 */
class Context implements InjectionAwareInterface
{

    use InjectableAwareTrait;

    protected $ctx = [];

    function __set($k, $v)
    {
        $this->ctx[$k] = $v;
    }

    function __get($k)
    {

        $value = $this->ctx[$k] ?? null;

        //先不管其它，跑起来再说
        return $value;
    }

}