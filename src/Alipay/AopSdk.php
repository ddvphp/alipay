<?php
namespace DdvPhp\Alipay;
use \DdvPhp\DdvUtil\String\Conversion;
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
        $filePath = self::$aopDir.DIRECTORY_SEPARATOR.$name.'.php';
        if (!is_file($filePath)){
            $filePath = self::$requestDir.DIRECTORY_SEPARATOR.$name.'.php';
        }
        if (is_file($filePath)){
            try {
                include $filePath;
            }catch (Exception $e){
                throw new Exception('autoload alipay file fail', 500, 'AUTOLOAD_ALIPAY_FILE_FAIL');
            }
        }
    }

    //转换编码
    public static function characetToUtf8($data) {
        if (! empty ( $data )) {
            $fileType = mb_detect_encoding ( $data, array (
                'UTF-8',
                'GBK',
                'GB2312',
                'LATIN1',
                'BIG5'
            ) );
            if ($fileType != 'UTF-8') {
                $data = mb_convert_encoding ( $data, 'UTF-8', $fileType );
            }
        }
        return $data;
    }

    /**
     * 使用SDK执行接口请求
     * @param unknown $request
     * @param string $token
     * @return Ambigous <boolean, mixed>
     */
    public static function aopclientRequestExecute($aopOrConfig, $request, $token = NULL) {
        if (is_array($aopOrConfig)){
            $aopOrConfig = self::getAopClient($aopOrConfig);
        }
        if (!($aopOrConfig instanceof AopClient)){
            throw new Exception('必须是一个aop实例化对象或者配置文件', 500, 'MUST_INSTANCEOF_AOP_RO_CONFIG_ARRAY');
        }
        $result = $aopOrConfig->execute($request, $token);
        return $result;
    }
    public static function getAopClient($config, $isMustConfig = false, $apiVersion = '1.0'){
        $config = self::getHumpConfig($config);
        if ($isMustConfig){
            if (empty($config['appId'])){
                throw new Exception('appId must config', 500, 'APP_ID_MUST_CONFIG');
            }
            if (empty($config['alipayPublicKey'])){
                throw new Exception('alipayPublicKey must config', 500, 'ALIPAY_PUBLIC_KEY_MUST_CONFIG');
            }
            if (empty($config['merchantPrivateKey'])){
                throw new Exception('merchantPrivateKey must config', 500, 'MERCHANT_PRIVATE_KEY_MUST_CONFIG');
            }
        }
        $aop = new AopClient();
        $aop->apiVersion = $apiVersion;
        isset($config['gatewayUrl']) && $aop->gatewayUrl = $config['gatewayUrl'];
        isset($config['appId']) && $aop->appId = $config['appId'];
        isset($config['merchantPrivateKey']) && $aop->rsaPrivateKey = $config['merchantPrivateKey'];
        isset($config['alipayPublicKey']) && $aop->alipayrsaPublicKey = $config['alipayPublicKey'];
        isset($config['signType']) && $aop->signType = $config['signType'];
        isset($config['charset']) && $aop->postCharset = $config['charset'];
        return $aop;
    }
    public static function getHumpConfig($config){
        foreach ($config as $key => $value) {
            $keyt = Conversion::underlineToHump($key);
            if ($keyt!==$key){
                unset($config[$key]);
                $config[$keyt] = $value;
            }
        }
        return $config;
    }
}
