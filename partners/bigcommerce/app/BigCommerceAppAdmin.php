<?php
include_once(dirname(__FILE__) . "/../data/BigCommerceAppDbAdapter.php");

/*
 * BigCommerceAppAdmin
 * Handles admin work for app callbacks (auth,
 * uninstall, and load). Functions to verify signed
 * requests, post back to BigCommerce, etc.
 *
 * @author		Tyler J Barnes
 * @contact		b4rn3scode@gmail.com
 * @version		1.0.1
 */
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/
class BigCommerceAppAdmin {

	/*	PROPS	*/

	// how script should operate
	protected $_role;
	// db adapter
	protected $_dbAdapter;
	// admin emails
	protected $_adminEmails;


	// AUTH
	private $_authCode;
	private $_authOAuthToken;
	private $_authAppUser = array();
	private $_authStoreHash;
	private $_authScope;
	private $_authGrantType;
	private $_authRedirectUri;
	private $_authPostbackUri;
	private $_authPostbackRequestSent;
	private $_authPostbackResponse;
	private $_authProcessSuccess;
	private $_authStatus;


	// AUTH SUBMIT
	protected $_subStoreHash;
	protected $_subStoreExists;
	protected $_subSCBCAccountsLinked;
	protected $_subLicense;
	protected $_subBCAccountInfo = array();
	protected $_subSCAccountId;


	// LOAD
	public $_loadSignedReq;
	private $_loadDecodedData = array();
	private $_loadRequestVerified;
	private $_loadState;
	private $_loadMessage;
	private $_loadResponseData = array();



	// STATIC
	public static $DEFAULT_AUTH_GRANT_TYPE = "authorization_code";
	public static $DEFAULT_AUTH_REDIRECT_URI = "https://sc.conversionvoodoo.com/partners/bigcommerce/auth.php";
	public static $DEFAULT_AUTH_POSTBACK_URI = "https://login.bigcommerce.com/oauth2/token";


	// CONSTANTS
	const AUTH_STORE_ADDED = 1;
	const AUTH_STORE_UPDATED = 2;
	const AUTH_STORE_READY = 3;
	const AUTH_STORE_FAILURE = 0;

	const LOAD_STATE_FAILED = -1;
	const LOAD_STATE_SUCCESS = 4;

	/*	END PROPS	*/



	/*
	 * ctor
	 *
	 * @param role string auth, load, uninstall (tells script how to operate)
	 * @param config array of config values to use
	 * @return void
	 */
	public function __construct($role = "auth", array $config = array()) {

		$this->_role = $role;
		$this->_dbAdapter = new BigCommerceAppDbAdapter();

		foreach($config as $prop => $val) {
			if(property_exists($this, $prop)) {
				$this->$prop = $val;
			}
		}

		if($this->_role == "auth") {

			$this->_authOAuthToken = null;
			$this->_authAppUser = array();
			// set to default values -- forces setter use
			$this->_authGrantType = self::$DEFAULT_AUTH_GRANT_TYPE;
			$this->_authRedirectUri = self::$DEFAULT_AUTH_REDIRECT_URI;
			$this->_authPostbackUri = self::$DEFAULT_AUTH_POSTBACK_URI;
			$this->_authPostbackRequestSent = false;
			$this->_authPostbackResponse = false;
			$this->_authProcessSuccess = false;

		} elseif($this->_role == "load") {

			$this->_loadDecodedData = array();
			$this->_loadSignedReq = null;
			$this->_loadRequestVerified = false;
			$this->_loadState = null;
			$this->_loadResponseData = array();
			$this->_loadMessage = null;

		}

		$this->_adminEmails = "tylerb@conversionvoodoo.com";
	}




	/**	ACCESSORS	**/
	public function getRole() { return $this->_role; }
	public function getAuthCode() { return $this->_authCode; }
	public function getAuthStoreHash() { return $this->_authStoreHash; }
	public function getAuthScope() { return (is_array($this->_authScope)) ? $this->_authScope : array(); }
	public function getAuthPostbackResponse() { return $this->_authPostbackResponse; }
	public function getAuthProcessSuccess() { return $this->_authProcessSuccess; }
	public function getSubStoreExists() { return $this->_subStoreExists; }
	public function getSubLicense() { return $this->_subLicense; }
	public function getSubSCAccountId() { return $this->_subSCAccountId; }
	public function getSubSCBCAccountsLinked() { return $this->_subSCBCAccountsLinked; }
	/*
	 * getSubBCAccountInfo
	 * Gets BC account info (all or just a certain field)
	 *
	 * @param field string field or array index to get
	 * @return array of info or field val
	 */
	public function getSubBCAccountInfo($field = null) {
		if(!isset($field) || is_null($field)) {
			return $this->_subBCAccountInfo;
		}
		if(isset($this->_subBCAccountInfo[$field])) {
			return $this->_subBCAccountInfo[$field];
		}

		return false;
	}


	public function getStoreUserInfo() {
		// TODO MAKE THIS LESS HACKY
		return array(
			"uid"	=>	(isset($this->_authAppUser["id"]) && !is_null($this->_authAppUser["id"])) ? $this->_authAppUser["id"] : "",
			"email"	=>	(isset($this->_authAppUser["email"]) && !is_null($this->_authAppUser["email"])) ? $this->_authAppUser["email"] : "",
			"store"	=>	$this->_authStoreHash,
		);
	}

	public function getLoadSignedReq() { return $this->_loadSignedReq; }
	/*
	 * getLoadDecodedData
	 * Gets the decoded load request data
	 *
	 * @param prop string property to get (optional, if omitted will return full array)
	 * @return mixed array or string of value(s)
	 */
	public function getLoadDecodedData($prop = null) {
		if(!isset($prop) || is_null($prop)) {
			return $this->_loadDecodedData;
		}
		if(isset($this->_loadDecodedData[$prop])) {
			return $this->_loadDecodedData[$prop];
		}
		return false;
	}

	public function getLoadRequestVerified() { return $this->_loadRequestVerified; }

	public function loadStateSuccess() {
		return ($this->_loadState === self::LOAD_STATE_SUCCESS);
	}

	public function getLoadMessage() { return $this->_loadMessage; }
	public function getLoadResponseData() { return $this->_loadResponseData; }



	public function setRole($role = "auth") { $this->_role = $role; }
	public function setAuthCode($code = "") {
		$this->_authCode = $code;
	}
	public function setAuthStoreHash($hash = "") {
		if(!empty($hash) && strlen($hash) > 0) {
			$this->_authStoreHash = $hash;
		}
	}
	public function setAuthScope(array $scope = array()) {
		$this->_authScope = $scope;
	}

	public function authStatusReady() {
		return ($this->_authStatus === self::AUTH_STORE_READY);
	}

	public function authPostbackRequestSent() {
		return ($this->_authPostbackRequestSent === true);
	}

	public function authPostbackRequestSuccess() {
		if($this->getAuthProcessSuccess()) {
			return true;
		}
		return (!($this->_authPostbackResponse == false));
	}

	private function setAuthProcessSuccess($status = false) {
		$this->_authProcessSuccess = $status;
	}

	public function setSubStoreExists($bool = false) {
		$this->_subStoreExists = $bool;
	}

	public function setSubLicense($lic = "") {
		$this->_subLicense = $lic;
	}

	public function setSubBCAccountInfo(array $info = array()) {
		foreach($info as $field => $val) {
			$info[$field] = intval($val);
		}
		$this->_subBCAccountInfo = $info;
	}

	public function setSubSCAccountId($id = 0) {
		$this->_subSCAccountId = $id;
	}

	public function setSubSCBCAccountsLinked($bool = false) {
		$this->_subSCBCAccountsLinked = $bool;
	}

	public function setLoadSignedReq($req = "") {
		$this->_loadSignedReq = $req;
	}

	public function setLoadDecodedData(array $data = array()) {
		$this->_loadDecodedData = $data;
	}

	public function setLoadRequestVerified($bool = false) {
		$this->_loadRequestVerified = $bool;
	}

	public function setLoadResponseData(array $data = array()) {
		$this->_loadResponseData = $data;
	}

	public function setLoadMessage($msg = "") {
		$this->_loadMessage = $msg;
	}



	/**	END ACCESSORS	**/




	/*
	 * initAuthSubmit
	 * Initializes auth submit
	 *
	 * @return void
	 */
	public function initAuthSubmit() {
		if($this->_role !="authSubmit") {
			throw new Exception("Invalid role");
		}
		if(is_null($this->_subStoreHash)) {
			throw new Exception("Invalid hash for auth submit");
		}

		$this->setSubStoreExists($this->_dbAdapter->storeExists($this->_subStoreHash));
		$this->setSubSCBCAccountsLinked($this->_dbAdapter->accountLinked($this->_subStoreHash));
	}




	/*
	 * generateSCLicense
	 * Generates license string for SC account
	 *
	 * @return string license
	 */
	public function generateSCLicense() {
		$alpha_num = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$lng = strlen($alpha_num);
		$lic = "";
		for($i = 0; $i < 12; $i++) {
			$lic .= $alpha_num[mt_rand(0,$lng-1)];
		}
		return $lic;
	}




	/*
	 * handleSCAccountCreation
	 * Handles stuff to create SC account
	 *
	 * @param domain string domain
	 * @param name string store name
	 * @return void
	 */
	public function handleSCAccountCreation($domain = "", $name = "") {
		if($this->_role !="authSubmit") {
			throw new Exception("Invalid role");
		}
		if(empty($domain) || strlen($domain) < 1) {
			throw new Exception("Domain cannot be empty");
		}
		if(empty($name) || strlen($name) < 1) {
			throw new Exception("Account name cannot be empty");
		}
		// generate license
		// while checking to make sure it doesnt exist already
		$this->setSubLicense($this->generateSCLicense());
		$licAttempts = 0;
		$licErr = false;
		while($this->_dbAdapter->scLicenseExists($this->getSubLicense())) {
			$licAttempts++;
			if($licAttempts >= MAX_LIC_ATTEMPTS) {
				$licErr = true;
				break;
			}
			$this->setSubLicense($this->generateSCLicense());
		}

		if($licErr) {
			throw new Exception("Max SC license generations met");
		}

		// pull BCAcc info from db
		$this->setSubBCAccountInfo($this->_dbAdapter->getBigCommerceAccountInfo($this->_subStoreHash));

		if($this->getSubSCBCAccountsLinked()) {
			$this->setSubSCAccountId($this->getSubBCAccountInfo("AccId"));
		} else {
			// insert new data into SC Acc table
			$this->setSubSCAccountId($this->_dbAdapter->createNewSCAccount($this->_subLicense, $domain, $name));
		}

		// update BCAcc tbl with new id
		$this->_dbAdapter->linkSCBCAccounts($this->getSubSCAccountId(), $this->getSubBCAccountInfo("Id"));
	}




	/*
	 * getAccountReport
	 * Gets full account report
	 *
	 * @return array of report data or JSON string of data
	 */
	public function getAccountReport($jsonify = false) {
		$reportData = $this->_dbAdapter->getFullAccountReport($this->getSubSCAccountId());
		return ($jsonify === true) ? json_encode($reportData, JSON_NUMERIC_CHECK) : $reportData;
	}




	/*
	 * activateBCAccount
	 * Activates the BC account
	 *
	 * @return void
	 */
	public function activateBCAccount() {
		$this->_dbAdapter->updateBCAccountActive($this->getSubBCAccountInfo("Id"));
	}




	/*
	 * initAuth
	 * Initializes script for auth callback by:
	 * 	- checking if store hash exists
	 * 	- inserting data if store does not exist
	 *  - update temp code if store does exist
	 * Scope stuff done based on auth status after this is called
	 *
	 * @param context string context
	 * @param code string temp code
	 * @param scopes array scopes
	 * @return void
	 */
	public function initAuth($context = "", $code = "", array $scopes = array()) {
		if($this->_role !="auth") {
			throw new Exception("Invalid role");
		}
		if(empty($context) || strlen($context) < 1 || strpos($context, "/") === false) {
			throw new Exception("Invalid context received");
		}
		if(empty($code) || strlen($code) < 1) {
			throw new Exception("Invalid code received");
		}
		if(empty($scopes) || count($scopes) < 1) {
			throw new Exception("Invalid scopes received");
		}

		$store_hash = explode("/", $context);
		$this->setAuthStoreHash($store_hash[1]);
		$this->setAuthCode($code);
		$this->setAuthScope($scopes);

		// check to see if store exists
		if($this->_dbAdapter->storeExists($this->_authStoreHash)) {
			// if exists, check to see if we should update the temporary code
			if($this->_dbAdapter->tempCodeExists($this->_authStoreHash, $this->_authCode)) {
				// consider updated
				$this->_authStatus = self::AUTH_STORE_UPDATED;
			// update to new temp code
			} else {

				try {
					$this->_dbAdapter->updateTempCode($this->_authStoreHash, $this->_authCode);
					$this->_authStatus = self::AUTH_STORE_UPDATED;
				} catch(Exception $e) {
					$this->_authStatus = self::AUTH_STORE_FAILURE;
				}
			}
		// if not exists, create account
		} else {

			try {
				$this->_dbAdapter->createNewAccount($this->_authStoreHash, $this->_authCode);
				$this->_authStatus = self::AUTH_STORE_ADDED;
			} catch(Exception $e) {
				$this->_authStatus = self::AUTH_STORE_FAILURE;
			}
		}

		$this->setAuthProcessSuccess($this->_dbAdapter->isAuthComplete($this->_authStoreHash));

		//$this->handleScope(); // TODO ADD BACK LATER... TESTING THE FUNCTION FOR NOW
	}




	/*
	 * handleScope
	 * Handles the scopes for the account by updating or inserting
	 * based on the authentication status
	 *
	 * TODO PRIVATIZE THIS FUNCTION AFTER TESTING ( private function handleScop() )
	 * 	SO ITS CALLED INTERNALLY FROM INITAUTH
	 *
	 * @return void
	 */
	public function handleScope() {
		if($this->_role !="auth") {
			throw new Exception("Invalid role");
		}
		if($this->_authStatus === self::AUTH_STORE_FAILURE) {
			throw new Exception("Cannot handle scopes with invalid auth status");
		}

		// if store just added, no scopes will be stored
		// store all scopes
		if($this->_authStatus === self::AUTH_STORE_ADDED) {

			// store all scopes
			$this->_dbAdapter->addScope($this->_authStoreHash, $this->_authScope);
			$this->_authStatus = self::AUTH_STORE_READY;

		// otherwise update scopes
		} elseif($this->_authStatus === self::AUTH_STORE_UPDATED) {
			$updateScopes = array();
			$addScopes = array();

			// if there are no stored scopes, store all scopes
			$currentScope = $this->_dbAdapter->getAppScope($this->_authStoreHash);
			if(empty($currentScope) || count($currentScope) < 1) {

				// store all scopes
				$this->_dbAdapter->addScope($this->_authStoreHash, $this->_authScope);
				$this->_authStatus = self::AUTH_STORE_READY;

			// otherwise figure out which ones to add/update
			} else {
				// array to reverse check scopes (if db scope !isset(in tmpScope[]) then
				// we must inactivate it)
				$tmpScope = array();
				foreach($this->_authScope as $i => $scope) {
					// store scope as array index so we can use isset() later for performance
					$tmpScope[$scope] = true;
					// check against stored scope
					if(isset($currentScope[$scope])) {

						// if inactive -> update to activate
						if(!(((bool)$currentScope[$scope]) === true)) {
							$updateScopes[$scope] = true;
						}
					// if not set -> add scope
					} else {
						$addScopes[] = $scope;
					}
				}
				// now perform reverse look up to see which scopes
				// we need to inactivate
				foreach($currentScope as $scope => $active) {
					if(!isset($tmpScope[$scope]) && $active === true) {
						$updateScopes[$scope] = false;
					}
				}

				unset($tmpScope);
			}
			if(count($updateScopes) > 0) {
				$this->_dbAdapter->updateScope($this->_authStoreHash, $updateScopes);
			}
			if(count($addScopes) > 0) {
				$this->_dbAdapter->addScope($this->_authStoreHash, $addScopes);
			}
			$this->_authStatus = self::AUTH_STORE_READY;
		}

	}




	/*
	 * sendAuthPostbackRequest
	 * Sends the POST request back to BigCommerce server
	 * with required data
	 *
	 * @return void
	 */
	public function sendAuthPostbackRequest() {
		if($this->_role !="auth") {
			throw new Exception("Invalid role");
		}
		if($this->_authPostbackRequestSent) {
			throw new Exception("Auth POST back request already sent");
		}

		//if(!$this->getAuthProcessSuccess()) {

			$this->_authPostbackRequestSent = false;

			$data = array(
				"client_id"			=>	urlencode(BC_CLIENT_ID),
				"client_secret"		=>	urlencode(BC_CLIENT_SEC),
				"code"				=>	urlencode($this->_authCode),
				"scope"				=>	urlencode(implode(" ", $this->_authScope)),
				"grant_type"		=>	urlencode($this->_authGrantType),
				"redirect_uri"		=>	urlencode($this->_authRedirectUri),
				"context"			=>	urlencode("stores/{$this->_authStoreHash}"),
			);

			$strData = "";
			foreach($data as $fld => $val) {
				$strData .= "{$fld}={$val}&";
			}
			rtrim($strData, "&");

			$headers = array(
				"Content-Type: application/x-www-form-urlencoded",
			);

			$ch = curl_init();

			curl_setopt($ch,CURLOPT_URL,$this->_authPostbackUri);
			curl_setopt($ch,CURLOPT_POST,count($data));
			curl_setopt($ch,CURLOPT_POSTFIELDS,$strData);
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch,CURLOPT_TIMEOUT,10);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,true);
			curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);

			$this->_authPostbackResponse = json_decode(curl_exec($ch), true);
			curl_close($ch);

		//}

		$this->_authPostbackRequestSent = true;

	}




	/*
	 * handleAuthPostbackResponse
	 * Handles the POST response
	 *
	 * @return true on success
	 */
	public function handleAuthPostbackResponse() {
		//if($this->getAuthProcessSuccess()) {
			//return true;
		//}
		if(!$this->_authPostbackRequestSent) {
			throw new Exception("Request not sent");
		}
		if($this->_authPostbackResponse === false || !$this->authPostbackResponseIsValid()) {
			throw new Exception("POST response is invalid");
		}

		$this->_authOAuthToken = $this->_authPostbackResponse["access_token"];

		$tmpScope = array();
		$scopes = explode(" ", $this->_authPostbackResponse["scope"]);
		foreach($scopes as $i => $s) {
			if(empty($s) || strlen($s) < 1) {
				continue;
			}
			$tmpScope[] = trim($s);
		}
		$this->setAuthScope($tmpScope);
		$this->handleScope();

		$this->_authAppUser = array(
			"id"	=>	(int)$this->_authPostbackResponse["user"]["id"],
			"email"	=>	$this->_authPostbackResponse["user"]["email"],
		);

		$tmpContext = explode("/", $this->_authPostbackResponse["context"]);
		if($tmpContext[1] != $this->_authStoreHash) {
			return false;
		}

		try {
			$this->_dbAdapter->updateOAuthToken($this->_authStoreHash, $this->_authOAuthToken);
			if(!$this->_dbAdapter->userExists($this->_authStoreHash, $this->_authAppUser["id"])) {
				$this->_dbAdapter->addUser($this->_authStoreHash, $this->_authAppUser["id"], $this->_authAppUser["email"]);
			}
			return true;
		} catch(Exception $e) {
			// TODO HANLDE
			return false;
		}

	}




	/*
	 * initLoad
	 * Initializes load callback logic
	 *
	 * @param signedReq string signed request
	 * @return void
	 */
	public function initLoad($signedReq = "") {
		if($this->_role != "load") {
			throw new Exception("Invalid role set");
		}
		// pass in param.
		if(isset($signedReq) && !empty($signedReq) && !is_null($signedReq) && (is_string($signedReq) && strlen($signedReq) > 0)) {
			$this->setLoadSignedReq($signedReq);
		}
		// otherwise it was set in constructor

		$this->setLoadRequestVerified($this->verifySignedRequest());

	}




	/*
	 * handleLoadRequest
	 * Handles the load request by verifying store and user data
	 * and will add user access to a store if user not found
	 *
	 * @return void
	 */
	public function handleLoadRequest() {
		if($this->_role != "load") {
			throw new Exception("Invalid role set");
		}

		if(!$this->getLoadRequestVerified()) {
			$this->_loadState = self::LOAD_STATE_FAILED;
			$this->setLoadMessage("There was an error validating the request. This isn't your fault, it's a network issue.<br>Please try loading the app again or contact tylerb@conversionvoodoo.com with this message");
		} else {
			if(!$this->_dbAdapter->storeExists($this->getLoadDecodedData("store_hash"))) {

				$this->_loadState = self::LOAD_STATE_FAILED;
				$this->setLoadMessage("Could not find an account with your store. Contact tylerb@conversionvoodoo.com with your information about the issue");

			} else {

				$hash = $this->getLoadDecodedData("store_hash");
				$user = $this->getLoadDecodedData("user");

				try {

					// check to see if user exists, if not - add user
					if(!$this->_dbAdapter->userExists($hash, $user["id"])) {

						$this->_dbAdapter->addUser($hash, $user["id"], $user["email"]);
						$this->setLoadMessage("User {$user["email"]} has been given access to your Snake Charmer for BigCommerce account");

					// if user exists, make sure they are active, if not active,
					// reenable account by activating it
					} else {

						if(!$this->_dbAdapter->userActive($hash, $user["id"])) {
							$this->_dbAdapter->updateUserActiveState($hash, $user["id"], true);
						}

						// verify email and update if different
						if(!$this->_dbAdapter->validateOwnerEmail($hash, $user["id"], $user["email"], true)) {
							$this->setLoadMessage("User {$user["email"]} was updated");
						}

					}

					// get load data
					$this->setLoadResponseData($this->_dbAdapter->getLoadData($hash, $user["email"]));
					$this->_loadState = self::LOAD_STATE_SUCCESS;

				} catch(Exception $e) {
					$this->_loadState = self::LOAD_STATE_FAILED;
					$this->setLoadMessage("An ERROR occured while loading application. Email tylerb@conversionvoodoo.com with this message: '{$e->getMessage()}'");
				}

			}
		}

	}




	/*
	 * authProcessSuccess
	 * Flags the auth process for a store as completed
	 *
	 * @return void
	 */
	public function authProcessSuccess() {
		$this->_dbAdapter->updateAuthComplete($this->_authStoreHash);
		$this->setAuthProcessSuccess(true);
	}




	/*
	 * authPostbackResponseIsValid
	 * Checks to make sure response is valie
	 *
	 * @return true if valid
	 */
	public function authPostbackResponseIsValid() {
		return (isset($this->_authPostbackResponse) && is_array($this->_authPostbackResponse) && isset($this->_authPostbackResponse["access_token"]));
	}




	/*
	 * verifySignedRequest
	 * Verifies the signed request from load callback and
	 * decodes the data
	 *
	 * @return true if verified
	 */
	public function verifySignedRequest() {
		$tmpReq = $this->getLoadSignedReq();
		if(empty($tmpReq) || is_null($tmpReq) || $tmpReq == false) {
			throw new Exception("Load signed request is invalid: [{$tmpReq}]");
		}

		unset($tmpReq);

		list($data, $sig) = explode(".", $this->getLoadSignedReq(), 2);
		$decodedSig = base64_decode($sig);
		$json = base64_decode($data);
		$decodedData = json_decode($json, true);

		$tmpSig = hash_hmac("sha256", $json, BC_CLIENT_SEC, false);
		if(!$this->hashEqual($decodedSig, $tmpSig)) {
			return false;
		}
		$this->setLoadDecodedData($decodedData);
		return true;

	}




	/*
	 * hashEqual
	 * Checks to see if hashes are equal
	 *
	 * @param hashA string first hash
	 * @param hashB string second hash
	 * @return true if both strings are equal
	 */
	public function hashEqual($hashA = "", $hashB = "") {
		return ($hashA === $hashB);
	}




	/*
	 * notifyAdmin
	 * Emails notification to admin
	 *
	 * @param subject string subject
	 * @param msg string message
	 * @return void
	 */
	public function notifyAdmin($subject = "", $msg = "") {
		mail($this->_adminEmails, $subject, $msg, "From: scbigcommerce@conversionvoodoo.com");
	}


}
?>
