<?php
include_once(dirname(__FILE__) . "/../app/BigCommerceAppAdmin.php");
include_once(dirname(__FILE__) . "/../app/BigCommerceDefaultAccount.php");
/*
 * authSubmit
 * Handles submit data from BC Auth form
 */
$domain = (isset($_REQUEST["domain"]) && !empty($_REQUEST["domain"]) && !is_null($_REQUEST["domain"])) ? $_REQUEST["domain"] : null;
$email = (isset($_REQUEST["email"]) && !empty($_REQUEST["email"]) && !is_null($_REQUEST["email"])) ? $_REQUEST["email"] : null;
$company = (isset($_REQUEST["company"]) && !empty($_REQUEST["company"]) && !is_null($_REQUEST["company"])) ? $_REQUEST["company"] : null;
$stamp = (isset($_REQUEST["ts"]) && !empty($_REQUEST["ts"]) && !is_null($_REQUEST["ts"])) ? $_REQUEST["ts"] : null;
$accemail = (isset($_REQUEST["accemail"]) && !empty($_REQUEST["accemail"]) && !is_null($_REQUEST["accemail"])) ? $_REQUEST["accemail"] : null;
$uid = (isset($_REQUEST["uid"]) && !empty($_REQUEST["uid"]) && is_numeric($_REQUEST["uid"])) ? $_REQUEST["uid"] : null;
$tkn = (isset($_REQUEST["tkn"]) && !empty($_REQUEST["tkn"]) && !is_null($_REQUEST["tkn"])) ? $_REQUEST["tkn"] : null;
$store = (isset($_REQUEST["store"]) && !empty($_REQUEST["store"]) && !is_null($_REQUEST["store"])) ? $_REQUEST["store"] : null;
$invalidated = (isset($_REQUEST["invalidated"]) && $_REQUEST["invalidated"] == "true");

if(is_null($domain) || is_null($email) || is_null($company) || is_null($stamp) || is_null($tkn) || is_null($store)) {
	end_proc("/* INVALID DATA RECEIVED */");
}

$authApp = new BigCommerceDefaultAccount("authSubmit", array("_subStoreHash"=>$store));

$authApp->initAuthSubmit();

if(!$authApp->getSubStoreExists()) {
	end_proc("/* INVALID STORE HASH */");
}


if(!$invalidated && strtolower($email) == strtolower($accemail)) {
	if(!validToken($stamp, $uid, $store, $tkn)) {
		end_proc("/* INVALID TOKEN RECEIVED */");
	}


	try {
		$authApp->handleSCAccountCreation($domain, $company);
		$authApp->activateBCAccount();

		// set up defaults
		$authApp->setup();
		$authApp->initDefaults();
		$authApp->handleDefaults();
		// end defaults

		$jsobj = $authApp->getAccountReport(true);
		$js = "window.BCA._procEnded = true; window.BCA.submitSuccess({$jsobj});";
		end_proc($js);

	} catch(Exception $e) {
		$js = "window.BCA._procEnded = true; window.BCA.submitError('{$e->getMessage()}');";
		end_proc($js);
	}


}

end_proc("/* FUNCTIONALITY NOT DEVELOPED YET, OR SOMETHING WENT WRONG */");

/*
 * validToken
 * Validates token
 *
 * @param ts string timestamp
 * @param u string userid
 * @param s string store
 * @param t string token
 * @return true if valid token
 */
function validToken($ts = "", $u = "", $s = "", $t = "") {
	$ntkn = md5(sprintf(BC_AUTHRESP_TOKEN, $ts, $u, $s));
	return ($t == $ntkn);
}


/*
 * end_proc
 * ends the process with a message for the user
 * used on failure
 *
 * @param msg (string) message to echo
 * @return void
 */
function end_proc($msg) {
	header("Content-Type: application/javascript");
	echo $msg;
	exit;
}

?>
