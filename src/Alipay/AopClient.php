<?php
namespace DdvPhp\Alipay;

class AopClient extends AopClientOrg {
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