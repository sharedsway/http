<?php
/**
 * Created by PhpStorm.
 * User: debian
 * Date: 19-8-11
 * Time: 上午12:08
 */

namespace Sharedsway\Http;


use Sharedsway\Common\Text;
use Sharedsway\Http\Http\MiddlewareArg;

class Http implements HttpInterface
{

    /**
     * @var \Generator
     */
    private $_gen;

    /**
     * 中间件
     * @var MiddlewareArg[]
     */
    protected $middleware = [];


    /**
     * @var Context
     */
    protected $context = null;

    /**
     * Http constructor.
     */
    public function __construct()
    {


        //给 request 和 response 赋值，目前 ，先直接把Swoole\Http\Request 和 Response 赋进去。后面再进行封装
    }

    public function setContext(Context $context)
    {
        $this->context = $context;
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

    /**
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return \Generator
     */
    private function _getMiddlewareGenerator()
    {
        foreach ($this->middleware as $v) {
            yield $v;
        }
    }


    /**
     * 匹配简单的uri，复杂的，后续专门写个路由
     * @param $middleUri
     * @param $realUri
     * @return bool
     */
    private static function _matchMiddlewareUri($middleUri, $realUri)
    {
        $realUri = preg_replace('/\/+/', '/', $realUri);

        if ('*' === $middleUri) return true;


        if($middleUri === $realUri) return true;

        // 以 * 结尾，则用startsWith 判断
        if (preg_match('/\*$/', $middleUri)) {
            $newUri = preg_replace('/\*+$/', '', $middleUri);

            if (Text::startsWith($realUri, $newUri)) {
                return true;
            }

        }
        return false;
    }

    /**
     * @return \Generator
     */
    private function _getMiddlewareGeneratorAvailable()
    {
        $realUri = $this->context->request->server['request_uri'];
        foreach ($this->middleware as $v) {
            $middlewareUri = $v->uri;

            if (!self::_matchMiddlewareUri($middlewareUri, $realUri)) {
                continue;
            }

            if (!is_callable($v->middleware)) {
                continue;
            }

            yield $v;
        }
    }

    /**
     * 进入下一个中间件
     */
    public function next()
    {


        $middlewareArg = $this->_gen->current();


        if (!$middlewareArg) {
            fwrite(STDOUT, 'no method' . PHP_EOL);
            return;
        }
        $method        = $middlewareArg->middleware;

        $this->_gen->next();

        //
        call_user_func($method, $this->context, function () {
            $this->next();
        });
    }


    /**
     *
     */
    private function _handle()
    {
        //$this->___gen = $this->_getMiddlewareGenerator();
        $this->_gen = $this->_getMiddlewareGeneratorAvailable();

        $this->next();

    }

    /**
     *
     */
    public function handle()
    {
        ob_start();
        // ---- script start


        $this->_handle();

        // ---- script end
        $content = ob_get_contents();
        ob_end_clean();
        $this->context->response->end($content);
    }
}