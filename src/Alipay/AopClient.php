<?php
namespace DdvPhp\Alipay;

class AopClient {
	protected function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt) {
		$localIp = isset ($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : "CLI";
		$logger = new LtLogger;
		$logger->conf["log_file"] = rtrim(AOP_SDK_WORK_DIR, '\\/') . '/' . "logs/aop_comm_err_" . $this->appId . "_" . date("Y-m-d") . ".log";
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
	/*
		页面提交执行方法
		@param：跳转类接口的request; $httpmethod 提交方式。两个值可选：post、get
		@return：构建好的、签名后的最终跳转URL（GET）或String形式的form（POST）
		auther:笙默
	*/
	public function pageExecute($request,$httpmethod = "POST") {

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
		$sysParams["alipay_sdk"] = $this->alipaySdkVersion;
		$sysParams["terminal_type"] = $request->getTerminalType();
		$sysParams["terminal_info"] = $request->getTerminalInfo();
		$sysParams["prod_code"] = $request->getProdCode();
		$sysParams["notify_url"] = $request->getNotifyUrl();
		$sysParams["return_url"] = $request->getReturnUrl();
		$sysParams["charset"] = $this->postCharset;

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
			$enCryptContent = AopEncrypt::encrypt($apiParams['biz_content'], $this->encryptKey);
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



	public function execute($request, $authToken = null, $appInfoAuthtoken = null) {

		$this->setupCharsets($request);

		//		//  如果两者编码不一致，会出现签名验签或者乱码
		if (strcasecmp($this->fileCharset, $this->postCharset)) {

			// writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
			throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
		}

		$iv = null;

		if (!$this->checkEmpty($request->getApiVersion())) {
			$iv = $request->getApiVersion();
		} else {
			$iv = $this->apiVersion;
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
			$enCryptContent = AopEncrypt::encrypt($apiParams['biz_content'], $this->encryptKey);
			$apiParams['biz_content'] = $enCryptContent;

		}


		//签名
		$sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->signType);


		//系统参数放入GET请求串
		$requestUrl = $this->gatewayUrl . "?";
		foreach ($sysParams as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($this->characet($sysParamValue, $this->postCharset)) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);


		//发起HTTP请求
		try {
			$resp = $this->curl($requestUrl, $apiParams);
		} catch (Exception $e) {

			$this->logCommunicationError($sysParams["method"], $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
			return false;
		}

		//解析AOP返回结果
		$respWellFormed = false;


		// 将返回结果转换本地文件编码
		$r = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);



		$signData = null;

		if ("json" == $this->format) {

			$respObject = json_decode($r);
			if (null !== $respObject) {
				$respWellFormed = true;
				$signData = $this->parserJSONSignData($request, $resp, $respObject);
			}
		} else if ("xml" == $this->format) {

			$respObject = @ simplexml_load_string($resp);
			if (false !== $respObject) {
				$respWellFormed = true;

				$signData = $this->parserXMLSignData($request, $resp);
			}
		}


		//返回的HTTP文本不是标准JSON或者XML，记下错误日志
		if (false === $respWellFormed) {
			$this->logCommunicationError($sysParams["method"], $requestUrl, "HTTP_RESPONSE_NOT_WELL_FORMED", $resp);
			return false;
		}

		// 验签
		$this->checkResponseSign($request, $signData, $resp, $respObject);

		// 解密
		if (method_exists($request,"getNeedEncrypt") &&$request->getNeedEncrypt()){

			if ("json" == $this->format) {


				$resp = $this->encryptJSONSignSource($request, $resp);

				// 将返回结果转换本地文件编码
				$r = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);
				$respObject = json_decode($r);
			}else{

				$resp = $this->encryptXMLSignSource($request, $resp);

				$r = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);
				$respObject = @ simplexml_load_string($r);

			}
		}

		return $respObject;
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

}