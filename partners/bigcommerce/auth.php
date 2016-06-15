<?php
date_default_timezone_set("America/Los_Angeles");

include_once(dirname(__FILE__) . "/app/BigCommerceAppAdmin.php");
/*
 * Auth callback from BigCommerce
 */
$tmpCode = (isset($_REQUEST["code"]) && !empty($_REQUEST["code"]) && !is_null($_REQUEST["code"]) && strlen($_REQUEST["code"]) > 0) ? $_REQUEST["code"] : null;
$context = (isset($_REQUEST["context"]) && !empty($_REQUEST["context"]) && !is_null($_REQUEST["context"]) && strlen($_REQUEST["context"]) > 0) ? $_REQUEST["context"] : null;
$scope = (isset($_REQUEST["scope"]) && !empty($_REQUEST["scope"]) && !is_null($_REQUEST["scope"]) && strlen($_REQUEST["scope"]) > 0) ? $_REQUEST["scope"] : null;

if(is_null($tmpCode) || is_null($context) || is_null($scope)) {
	include_once("views/error.php");
	exit;
}

$authApp = new BigCommerceAppAdmin();

try {


	$scopeList = explode(" ", $scope);
	foreach($scopeList as $i => $s) {
		if(empty($s) || strlen($s) < 1) {
			unset($scopeList[$i]);
		} else {
			$scopeList[$i] = trim($s);
		}
	}

	$authApp->initAuth($context, $tmpCode, $scopeList);
	// TODO : REMOVE WHEN TESTIN DONE AND SCOPE FUNCTION CALLED BY initAuth
	$authApp->handleScope();

	if($authApp->authStatusReady()) {
		$authApp->sendAuthPostbackRequest();
		if(!$authApp->authPostbackRequestSuccess()) {

			//echo "<!---"; var_dump($authApp); echo "--->";
			$data = "---------------------------\n\n";
			$data .= " -- AUTH FAILED TO POST BACK:\nCode\t{$tmpCode}\nContext\t{$context}\nScope\t{$scope}\n";
			$data .= "DATA: {$authApp->getAuthPostbackResponse()}\n";
			$data = "---------------------------\n\n";
			file_put_contents("/var/www/logs/auth_err.log", $data, FILE_APPEND);
			$authApp->notifyAdmin("AUTH POST BACK FAILURE", $data);
			$ERR_MSG = "Failed to authenticate. Please email tylerb@conversionvoodoo with ERROR CODE {$tmpCode}-{$context}";
			include_once("views/error.php");

			exit;


		// SUCCESS
		// SUCCESS
		} else {
			if($authApp->handleAuthPostbackResponse()) {

				$authApp->authProcessSuccess();
				$authApp->notifyAdmin("AUTH callback success", "Code\t{$tmpCode}\nContext\t{$context}\nScope\t{$scope}");
				$STORE_USER_DATA = $authApp->getStoreUserInfo();
				include_once("views/auth.php");
				exit;

			} else {

				//echo "<!---"; var_dump($authApp); echo "--->";
				$data = "---------------------------\n\n";
				$data .= " -- FAILED TO HANDLE POST RESPONSE:\nCode\t{$tmpCode}\nContext\t{$context}\nScope\t{$scope}\n";
				$data .= "RESPONSE: {$authApp->getAuthPostbackResponse()}\n";
				$data = "---------------------------\n\n";
				file_put_contents("/var/www/logs/auth_err.log", $data, FILE_APPEND);
				$authApp->notifyAdmin("FAILED TO HANDLE POST RESPONSE", $data);
				$ERR_MSG = "Failed to Create a user for your account. Please email tylerb@conversionvoodoo with ERROR CODE {$authApp->getAuthPostbackResponse()}";
				include_once("views/error.php");
				exit;

			}
		}


	} else {

		//echo "<!---"; var_dump($authApp); echo "--->";
		$data = "---------------------------\n\n";
		$data .= " -- FAILED TO INIT AUTH:\nCode\t{$tmpCode}\nContext\t{$context}\nScope\t{$scope}\n";
		$data = "---------------------------\n\n";
		file_put_contents("/var/www/logs/auth_err.log", $data, FILE_APPEND);
		$authApp->notifyAdmin("AUTH INIT AUTH FAILURE", $data);
		$ERR_MSG = "Failed to initialize authentication. Please email tylerb@conversionvoodoo with ERROR CODE {$tmpCode}-{$context}";
		include_once("views/error.php");
		exit;

	}


} catch(Exception $e) {
	$ERR_MSG = $e->getMessage();
	$data = "---------------------------\n\n";
	$data .= " -- GENERAL AUTH FAILURE:\nCode\t{$tmpCode}\nContext\t{$context}\nScope\t{$scope}\n";
	$data = "---------------------------\n\n";
	file_put_contents("/var/www/logs/auth_err.log", $data, FILE_APPEND);
	$authApp->notifyAdmin("AUTH Exception Caught", "{$ERR_MSG}\nCode {$tmpCode}\nContext {$context}\nScope {$scope}");
	include_once("views/error.php");
	exit;
}
?>
