<?php
namespace DdvPhp\Alipay;

class AopClient extends AopClientOrg {
    private $fileCharset = "UTF-8";
	protected function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt) {
		$localIp = isset ($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : "CLI";
		$logger = new LtLogger;
		$logger->conf["log_file"] = rtrim(AopSdk::$aopSdkWorkDir, '\\/') . '/' . "logs/aop_comm_err_" . $this->appId . "_" . date("Y-m-d") . ".log";
		$logger->conf["separator"] = "^_^";
		$logData = array(
			date("Y-m-d H:i:s"),
			$apiName,
			$this->appId,
			$localIp,
			PHP_OS,
			$this->alipaySdkVersion,
			$requestUrl,
			$errorCode,
			str_replace("\n", "", $responseTxt)
		);
		$logger->log($logData);
	}


    public function exec($paramsArray) {
		if (!isset ($paramsArray["method"])) {
			trigger_error("No api name passed");
		}
		$inflector = new LtInflector;
		$inflector->conf["separator"] = ".";
		$requestClassName = ucfirst($inflector->camelize(substr($paramsArray["method"], 7))) . "Request";
		if (!class_exists($requestClassName)) {
			trigger_error("No such api: " . $paramsArray["method"]);
		}

		$session = isset ($paramsArray["session"]) ? $paramsArray["session"] : null;

		$requestClassName = '\\'.$requestClassName;
		$req = new $requestClassName;
		foreach ($paramsArray as $paraKey => $paraValue) {
			$inflector->conf["separator"] = "_";
			$setterMethodName = $inflector->camelize($paraKey);
			$inflector->conf["separator"] = ".";
			$setterMethodName = "set" . $inflector->camelize($setterMethodName);
			if (method_exists($req, $setterMethodName)) {
				$req->$setterMethodName ($paraValue);
			}
		}
		return $this->execute($req, $session);
	}

    private function setupCharsets($request) {
        if ($this->checkEmpty($this->postCharset)) {
            $this->postCharset = 'UTF-8';
        }
        $str = preg_match('/[\x80-\xff]/', $this->appId) ? $this->appId : print_r($request, true);
        $this->fileCharset = mb_detect_encoding($str, "UTF-8, GBK") == 'UTF-8' ? 'UTF-8' : 'GBK';
    }
    /*
        页面提交执行方法
        @param：跳转类接口的request; $httpmethod 提交方式。两个值可选：post、get
        @return：构建好的、签名后的最终跳转URL（GET）或String形式的form（POST）
        auther:笙默
    */
    public function pageExecuteApp($request, $authToken = null, $appInfoAuthtoken = null, $httpmethod = "POST") {

        $this->setupCharsets($request);

        if (strcasecmp($this->fileCharset, $this->postCharset)) {

            // writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
            throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
        }

        $iv=null;

        if(!$this->checkEmpty($request->getApiVersion())){
            $iv=$request->getApiVersion();
        }else{
            $iv=$this->apiVersion;
        }

        //组装系统参数
        $sysParams["app_id"] = $this->appId;
        $sysParams["version"] = $iv;
        $sysParams["format"] = $this->format;
        $sysParams["sign_type"] = $this->signType;
        $sysParams["method"] = $request->getApiMethodName();
        $sysParams["timestamp"] = date("Y-m-d H:i:s");
        $sysParams["auth_token"] = $authToken;
        $sysParams["alipay_sdk"] = $this->alipaySdkVersion;
        $sysParams["terminal_type"] = $request->getTerminalType();
        $sysParams["terminal_info"] = $request->getTerminalInfo();
        $sysParams["prod_code"] = $request->getProdCode();
        $sysParams["notify_url"] = $request->getNotifyUrl();
        $sysParams["return_url"] = $request->getReturnUrl();
        $sysParams["charset"] = $this->postCharset;
        $sysParams["app_auth_token"] = $appInfoAuthtoken;

        //获取业务参数
        $apiParams = $request->getApiParas();

        if (method_exists($request,"getNeedEncrypt") &&$request->getNeedEncrypt()){

            $sysParams["encrypt_type"] = $this->encryptType;

            if ($this->checkEmpty($apiParams['biz_content'])) {

                throw new Exception(" api request Fail! The reason : encrypt request is not supperted!");
            }

            if ($this->checkEmpty($this->encryptKey) || $this->checkEmpty($this->encryptType)) {

                throw new Exception(" encryptType and encryptKey must not null! ");
            }

            if ("AES" != $this->encryptType) {

                throw new Exception("加密类型只支持AES");
            }

            // 执行加密
            $enCryptContent = encrypt($apiParams['biz_content'], $this->encryptKey);
            $apiParams['biz_content'] = $enCryptContent;

        }

        //print_r($apiParams);
        $totalParams = array_merge($apiParams, $sysParams);

        //待签名字符串
        $preSignStr = $this->getSignContent($totalParams);

        //签名
        $totalParams["sign"] = $this->generateSign($totalParams, $this->signType);

        if ("GET" == strtoupper($httpmethod)) {

            //value做urlencode
            $preString=$this->getSignContentUrlencode($totalParams);
            //拼接GET请求串
            $requestUrl = $this->gatewayUrl."?".$preString;

            return $requestUrl;
        } else {
            //拼接表单字符串
            return $this->buildRequestForm($totalParams);
        }


    }

}