<?php


namespace easy;

use easy\exception\ErrorException;
use easy\exception\Handle;
use easy\exception\InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;
use ReflectionClass;

/**
 * Class App
 * @property Config $config
 * @property Request $request
 * @property Response $response
 * @property Db $db
 * @property Handle $handle
 * @property Dispatch $dispatch
 * @method string getEasyPath
 * @method string getRootPath
 * @method string getAppPath
 * @method string getRuntimePath
 * @package easy
 */
class App
{
    /****路径相关 开始*******/
    protected $path=[
        'easy'=>'',
        'root'=>'',
        'app'=>'',
        'runtime'=>'',
    ];
    /**@var Container $container */
    protected $container;
    public function __construct($rootPath='')
    {
        $this->path['easy'] = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $this->path['root'] = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $this->getDefaultRootPath();
        $this->path['app'] = $this->path['root'] . 'app' . DIRECTORY_SEPARATOR;
        $this->path['runtime'] = $this->path['root'] . 'runtime' . DIRECTORY_SEPARATOR;
        $this->container=Container::getInstance();
        $this->container->bind('app',$this);
    }
    protected function getDefaultRootPath(){
        return dirname($this->path['easy'], 4) . DIRECTORY_SEPARATOR;
    }

   public function __call($name, $arguments)
    {
        if(substr($name,0,3)==='get' && substr($name,-4)==='Path')
        {
            return $this->getPath(strtolower(substr($name,3,-4)));
        }
    }
    /**
     *
     * @param string $path
     * @return ?string
     */
    public function getPath($path)
    {
        if(!array_key_exists($path,$this->path))
        {
            return null;
        }
        return $this->path[$path];
    }
    /****路径相关 结束*******/


    protected $binds=[
        'config'=>Config::class,
        'request'=>Request::class,
        'response'=>Response::class,
        'db'=>Db::class,
        'handle'=>Handle::class,
        'dispatch'=>Dispatch::class,
    ];

    //容器魔方方法
    public function __get($name){
        return $this->container->get($name);
    }


    public function runningInConsole()
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }
    public function run(){
        error_reporting(E_ALL);
        try {
            $this->init();
            $this->dispatch->run();
        }
        catch (\Exception $e)
        {
            //exception
        }
        catch (\Throwable $e)
        {
            //error
        }
        if(isset($e))
        {
            throw $e;
            $this->handle->report($e);
        }
    }
    protected function init(){
        set_error_handler([$this, 'appError']);
        foreach ($this->binds as $k=>$v)
        {
            $this->container->set($k,$v);
        }
    }
    public function appError(int $errno, string $errstr, string $errfile , int $errline )
    {
        $exception = new ErrorException( $errstr,$errno, $errfile, $errline);
        if (error_reporting() & $errno) {
            throw $exception;
        }
    }
    
}