<?php
namespace DdvPhp\Alipay;
/**
 * AOP SDK 入口文件
 * 请不要修改这个文件，除非你知道怎样修改以及怎样恢复
 * @author wuxiao
 */

class AopSdk
{
    public static $AOP_SDK_WORK_DIR = '/tmp/';
    public static $AOP_SDK_DEV_MODE = false;
    public static $libRootDir = '';
    public static $aopDir = '';
    public static $requestDir = '';
    public static function init($AOP_SDK_WORK_DIR = null, $AOP_SDK_DEV_MODE = false){
        if (!empty($AOP_SDK_WORK_DIR)) {
            self::$AOP_SDK_WORK_DIR = $AOP_SDK_WORK_DIR;
        }
        if (is_bool($AOP_SDK_DEV_MODE)) {
            self::$AOP_SDK_DEV_MODE = $AOP_SDK_DEV_MODE;
        }
        self::$libRootDir = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../../org';
        self::$aopDir = self::$libRootDir . DIRECTORY_SEPARATOR . 'aop';
        self::$requestDir = self::$aopDir . DIRECTORY_SEPARATOR . 'request';
        spl_autoload_register(AopSdk::class.'::autoload');
        /**
         * 定义常量开始
         * 在include("AopSdk.php")之前定义这些常量，不要直接修改本文件，以利于升级覆盖
         */
        /**
         * SDK工作目录
         * 存放日志，AOP缓存数据
         */
        defined("AOP_SDK_WORK_DIR") || define("AOP_SDK_WORK_DIR", self::$AOP_SDK_WORK_DIR);
        /**
         * 是否处于开发模式
         * 在你自己电脑上开发程序的时候千万不要设为false，以免缓存造成你的代码修改了不生效
         * 部署到生产环境正式运营后，如果性能压力大，可以把此常量设定为false，能提高运行速度（对应的代价就是你下次升级程序时要清一下缓存）
         */
        defined("AOP_SDK_DEV_MODE") || define("AOP_SDK_DEV_MODE", $AOP_SDK_DEV_MODE);
        /**
         * 定义常量结束
         */

    }
    public static function autoload($name){
        var_dump($name);die;
        /* 
        global $apiHome;  
        try {  
                include $apiHome.'top/request/'.$name.'.php';  
        }catch (Exception $e){  
                echo $e->getMessage();  
                exit;  
        }  */
    } 
}