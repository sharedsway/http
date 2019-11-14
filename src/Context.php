<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-10
 * Time: 下午11:42
 */
namespace Sharedsway\Http;
use Sharedsway\Di\DiInterface;
use Sharedsway\Di\Exception;
use Sharedsway\Di\Library\InjectableAwareTrait;
use Sharedsway\Di\Library\InjectionAwareInterface;


/**
 * Class Context
 * @package Sharedsway\Http
 * @property $request
 * @property $response
 * @property DiInterface $di
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


        if (!$value) {
            try {

                $value = $this->getDI()->get($k);
            } catch (Exception $exception) {
                return null;
            }
        }
        return $value;
    }

}