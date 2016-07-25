<?php
include_once(dirname(__FILE__) . "/../../../include/config.php");
include_once(dirname(__FILE__) . "/../../../include/DBCon.php");

/*
 * BigCommerceAppDbAdapter
 * DB adapter for the app
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
class BigCommerceAppDbAdapter {

	// db object
	private $_db;


	/*
	 * c'tor
	 *
	 * @param db DBCon object
	 * @return void
	 */
	public function __construct($db = null) {
		if(isset($db) && !is_null($db) && ($db instanceof DBCon)) {
			$this->_db = $db;
		} else {
			$this->_db = new DBCon();
		}

		if(!$this->_db->Link()) {
			throw new Exception("Cannot establish database connection with BigCommerceAppDbAdapter DB Object");
		}
	}



	/*
	 * storeExists
	 * Checks to see if a store exists based on store hash
	 *
	 * @param hash string hash to check for
	 * @return true if store exists
	 */
	public function storeExists($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);

		$sql = "SELECT COUNT(*) AS TOT FROM BigCommerceAccount WHERE StoreHash = '{$hash}'";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}



	/*
	 * isAuthComplete
	 * Checks to see if auth process has been completed
	 *
	 * @param hash string store hash
	 * @return true if AuthComplete set to true
	 */
	public function isAuthComplete($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);

		$sql = "SELECT COUNT(*) AS TOT FROM BigCommerceAccount WHERE StoreHash = '{$hash}' AND AuthComplete = 1";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}




	/*
	 * createNewAccount
	 * Creates a new account with basic data
	 *
	 * @param hash string store hash
	 * @param code string temp code
	 * @return void
	 */
	public function createNewAccount($hash = "", $code = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($code) || strlen($code) < 1) {
			throw new Exception("Temp code cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$code = $this->_db->EscapeQueryStmt($code);

		$sql = "INSERT INTO BigCommerceAccount (Id, AccId, TempCode, StoreHash, OAuthToken, Active, Del, DateAdded) VALUES (NULL, 0, '{$code}', '{$hash}', '', 0, 0, CURRENT_TIMESTAMP)";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
		if($this->_db->GetLastInsertedId() < 1) {
			// TODO HANDLE
		}
	}





	/*
	 * tempCodeExists
	 * Checks to see if the temporary code for a store still
	 * exists or if it has been updated
	 *
	 * @param hash string store hash
	 * @param code string the temp code to check for
	 * @return true if code exists for that store
	 */
	public function tempCodeExists($hash = "", $code = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($code) || strlen($code) < 1) {
			throw new Exception("Temp code cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$code = $this->_db->EscapeQueryStmt($code);

		$sql = "SELECT COUNT(*) AS TOT FROM BigCommerceAccount WHERE StoreHash = '{$hash}' AND TempCode = '{$code}'";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}




	/*
	 * updateTempCode
	 * Updates the temp code for a store
	 *
	 * @param hash string store hash
	 * @param code string the temp code to update to
	 * @return void
	 */
	public function updateTempCode($hash = "", $code = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($code) || strlen($code) < 1) {
			throw new Exception("Temp code cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$code = $this->_db->EscapeQueryStmt($code);

		$sql = "UPDATE BigCommerceAccount SET TempCode = '{$code}' WHERE StoreHash = '{$hash}'";

		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}

		// TODO ENSURE UPDATE?
	}




	/*
	 * getAppScope
	 * Gets a list of the scopes associated with a store
	 *
	 * @param hash string store hash
	 * @return array of scopes
	 */
	public function getAppScope($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);

		$sql = "SELECT s.Scope, s.Active FROM BigCommerceAccountAppScope AS s INNER JOIN BigCommerceAccount AS bca ON bca.Id = s.BigCommerceAccountId WHERE bca.StoreHash = '{$hash}'";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return array();
		}

		$scope_arr = array();
		$scopes = $this->_db->GetAll();
		foreach($scopes as $idx => $data) {
			if(isset($data["Scope"]) && isset($data["Active"])) {
				$scope_arr[$data["Scope"]] = ((bool)$data["Active"]);
			}
		}

		return $scope_arr;
	}




	/*
	 * scopeExists
	 * Checks to see if a scope exists for app
	 *
	 * @param hash string store hash
	 * @param scope string scrop to check for
	 * @return true if exists
	 */
	public function scopeExists($hash = "", $scope = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($scope) || strlen($scope) < 1) {
			throw new Exception("Scope cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$scope = $this->_db->EscapeQueryStmt($scope);

		$sql = "SELECT COUNT(*) AS TOT FROM BigCommerceAccountAppScope WHERE Scope = '{$scope}' AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);

	}



	/*
	 * addScope
	 * Adds a scope to an app account
	 *
	 * @param hash string store hash
	 * @param rray of scopes to add
	 * @return void
	 */
	public function addScope($hash = "", $scope = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($scope) || !is_array($scope) || count($scope) < 1) {
			throw new Exception("Scope cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);

		foreach($scope as $i => $s) {
			$s = $this->_db->EscapeQueryStmt($s);

			$sql = "INSERT INTO BigCommerceAccountAppScope (Id, BigCommerceAccountId, Scope, Active) VALUES (NULL, (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}'), '{$s}', 1)";
			if(!$this->_db->Query($sql)) {
				//TODO HANDLE
			}
			if($this->_db->GetLastInsertedId() < 1) {
				// TODO HANDLE
			}
		}

	}



	/*
	 * updateScope
	 * Marks an existing scope as active or inactive
	 *
	 * @param hash string store hash
	 * @param scope_data array of scope data (array("scope"=>true|false))
	 * @return void
	 */
	public function updateScope($hash = "", array $scope_data = array()) {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($scope_data) || count($scope_data) < 1) {
			throw new Exception("Scope data cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);

		foreach($scope_data as $scope => $isActive) {
			$strActive = ($isActive === true) ? "1" : "0";
			$strScope = $this->_db->EscapeQueryStmt($scope);
			$this->_db->Query("UPDATE BigCommerceAccountAppScope SET Active = {$strActive} WHERE Scope = '{$strScope}' AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')");
		}

		//TODO ENSURE UPDATED? REPORT FAILURES?
	}




	/*
	 * updateOAuthToken
	 * Updates OAuth token for account
	 *
	 * @param hash string store hash
	 * @param token string OAuth token
	 * @return void
	 */
	public function updateOAuthToken($hash = "", $token = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(empty($token) || strlen($token) < 1) {
			throw new Exception("OAuth token cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$token = $this->_db->EscapeQueryStmt($token);

		$sql = "UPDATE BigCommerceAccount SET OAuthToken = '{$token}' WHERE StoreHash = '{$hash}'";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}


	}



	/*
	 * userExists
	 * Checks to see if a BigCommerce user exists
	 * based on user id from BC
	 *
	 * @param hash string sotre hash
	 * @param uid int user id
	 * @return true if user exists
	 */
	public function userExists($hash = "", $uid = 0) {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(!is_numeric($uid) || $uid < 1) {
			throw new Exception("Invalid user id");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$uid = $this->_db->EscapeQueryStmt($uid);

		$sql = "SELECT COUNT(*) AS TOT FROM BigCommerceAccountUser WHERE UserId = {$uid} AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}




	/*
	 * addUser
	 * Adds a user
	 *
	 * @param hash string store has
	 * @param uid int user id
	 * @param email string user email
	 * @return void
	 */
	public function addUser($hash = "", $uid = 0, $email = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(!is_numeric($uid) || $uid < 1) {
			throw new Exception("Invalid user id");
		}
		if(empty($email) || strlen($email) < 1) {
			throw new Exception("User email cannot be empty");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$uid = $this->_db->EscapeQueryStmt($uid);
		$email = $this->_db->EscapeQueryStmt(strtolower($email));

		$sql = "INSERT INTO BigCommerceAccountUser (Id, BigCommerceAccountId, UserId, UserEmail, Active, Del, TimeAdded) VALUES (NULL, (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}'), {$uid}, '{$email}', 1, 0, CURRENT_TIMESTAMP)";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
		if($this->_db->GetLastInsertedId() < 1) {
			// TODO HANDLE
		}

	}




	/*
	 * updateUserActiveState
	 * Sets user access to active to inactive
	 *
	 * @param hash string store hash
	 * @param uid integer userid
	 * @param active bool true to set to active
	 * @return void
	 */
	public function updateUserActiveState($hash = "", $uid = 0, $active = false) {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(!is_numeric($uid) || $uid < 1) {
			throw new Exception("Invalid user id");
		}
		if(!($active === true || $active === false)) {
			throw new Exception("Invalid active state received");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);
		$uid = $this->_db->EscapeQueryStmt($uid);
		$activeStr = ($active) ? "1" : "0";

		$sql = "UPDATE BigCommerceAccountUser SET Active = {$activeStr} WHERE UserId = {$uid} AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
	}




	/*
	 * userActive
	 * Checks to see if user is active for a store (has access)
	 *
	 * @param hash string store hash
	 * @param uid int user id
	 * @return true if user active for store
	 */
	public function userActive($hash = "", $uid = 0) {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(!is_numeric($uid) || $uid < 1) {
			throw new Exception("Invalid user id");
		}
		$hash = $this->_db->EscapeQueryStmt($hash);
		$uid = $this->_db->EscapeQueryStmt($uid);
		$sql = "SELECT Active FROM BigCommerceAccountUser WHERE UserId = {$uid} AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}
		$active = $this->_db->GetRow();

		return (isset($active["Active"]) && intval($active["Active"]) > 0);
	}




	/*
	 * validateOwnerEmail
	 * Validates that owner email hasnt changed
	 *
	 * @param hash string store hash
	 * @param uid integer userid
	 * @param email string user email
	 * @param update bool true will update if it is different
	 * @return true if email the same
	 */
	public function validateOwnerEmail($hash = "", $uid = 0, $email = "", $update = false) {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(!is_numeric($uid) || $uid < 1) {
			throw new Exception("Invalid user id");
		}
		if(empty($email) || strlen($email) < 1) {
			throw new Exception("Email cannot be empty");
		}
		$hash = $this->_db->EscapeQueryStmt($hash);
		$uid = $this->_db->EscapeQueryStmt($uid);
		$email = $this->_db->EscapeQueryStmt(strtolower($email));

		$sql = "SELECT COUNT(*) AS TOT FROM BigCommerceAccountUser WHERE UserId = {$uid} AND UserEmail = '{$email}' AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		if(isset($tot["TOT"]) && intval($tot["TOT"]) > 0) {
			return true;
		} else {
			$this->updateBCUserEmail($hash, $uid, $email);
			return false;
		}

	}




	/*
	 * updateBCUserEmail
	 * Updates email for BC user
	 *
	 * @param hash string store hash
	 * @param uid int userid
	 * @param email string email to use
	 * @return void
	 */
	public function updateBCUserEmail($hash = "", $uid = 0, $email = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		if(!is_numeric($uid) || $uid < 1) {
			throw new Exception("Invalid user id");
		}
		if(empty($email) || strlen($email) < 1) {
			throw new Exception("Email cannot be empty");
		}
		$hash = $this->_db->EscapeQueryStmt($hash);
		$uid = $this->_db->EscapeQueryStmt($uid);
		$email = $this->_db->EscapeQueryStmt(strtolower($email));

		$sql = "UPDATE BigCommerceAccountUser SET UserEmail = '{$email}' WHERE UserId = {$uid} AND BigCommerceAccountId = (SELECT Id FROM BigCommerceAccount WHERE StoreHash = '{$hash}')";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
	}





	/*
	 * updateAuthComplete
	 * Updates Auth Complete to true
	 *
	 * @param hash string store hash
	 * @return void
	 */
	public function updateAuthComplete($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		$hash = $this->_db->EscapeQueryStmt($hash);
		$sql = "UPDATE BigCommerceAccount SET AuthComplete = 1 WHERE StoreHash = '{$hash}'";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
	}



	/*
	 * scLicenseExists
	 * Checks to see if SC license exists
	 *
	 * @param lic string license to check for
	 * @return true if license exists
	 */
	public function scLicenseExists($lic = "") {
		if(empty($lic) || strlen($lic) < 1) {
			throw new Exception("License cannot be empty");
		}

		$lic = $this->_db->EscapeQueryStmt($lic);

		$sql = "SELECT COUNT(*) AS TOT FROM Account WHERE License = '{$lic}'";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}



	/*
	 * createNewSCAccount
	 * Creates a new SC account
	 *
	 * @param license string license
	 * @param domain string domain
	 * @param name string account name
	 * @return int last inserted id
	 */
	public function createNewSCAccount($license = "", $domain = "", $name = "") {
		if(empty($license) || strlen($license) < 1) {
			throw new Exception("License cannot be empty");
		}
		if(empty($domain) || strlen($domain) < 1) {
			throw new Exception("Domain cannot be empty");
		}
		if(empty($name) || strlen($name) < 1) {
			throw new Exception("Account name cannot be empty");
		}

		$license = $this->_db->EscapeQueryStmt($license);
		$domain = $this->_db->EscapeQueryStmt($domain);
		$name = $this->_db->EscapeQueryStmt($name);

		$sql = "INSERT INTO Account (Id, License, Domain, Name, Active, Del, Added) VALUES (NULL, '{$license}', '{$domain}', '{$name}', 1, 0, CURRENT_TIMESTAMP)";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return 0;
		}

		return $this->_db->GetLastInsertedId();
	}




	/*
	 * scAccountHasTheme
	 * Checks to see if a theme is already created for
	 * an account
	 *
	 * @param scaid int sc account ID
	 * @return true if theme already created
	 */
	public function scAccountHasTheme($scaid = 0) {
		if(!is_numeric($scaid) || $scaid < 1) {
			throw new Exception("Cannot check for them with invalid SC Account ID");
		}

		$scaid = $this->_db->EscapeQueryStmt($scaid);

		$sql = "SELECT COUNT(*) AS TOT FROM Theme WHERE AccId = {$scaid}";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}




	 /*
	  * getBigCommerceAccountInfo
	  * Gets basic BCAcc info
	  *
	  * @param hash string store hash
	  * @return array of account info
	  */
	 public function getBigCommerceAccountInfo($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		$hash = $this->_db->EscapeQueryStmt($hash);
		$sql = "SELECT bca.Id, bca.AccId, bca.AuthComplete, bca.Active, COUNT(bcau.Id) AS TotUsers FROM BigCommerceAccount AS bca INNER JOIN BigCommerceAccountUser AS bcau ON bcau.BigCommerceAccountId = bca.Id WHERE bca.StoreHash = '{$hash}'";

		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
		return $this->_db->GetRow();
	 }



	/*
	 * getSCAccountName
	 * Gets SC Account name
	 *
	 * @param aid int account id
	 * @return string account name
	 */
	public function getSCAccountName($aid = 0) {
		if(!is_numeric($aid) || $aid < 1) {
			throw new Exception("Cannot get account name. Invalid account ID");
		}
		$aid = $this->_db->EscapeQueryStmt($aid);
		$sql = "SELECT Name FROM Account WHERE Id = {$aid}";
		if(!$this->_db->Query($sql)) {
			throw new Exception("Failed to get account name for account id {$aid}. Query Failed");
		}
		$result = $this->_db->GetRow();

		return (isset($result["Name"]) && !is_null($result["Name"])) ? $result["Name"] : "";
	}




	/*
	 * linkSCBCAccounts
	 * Links a BigCommerce account to the root SC
	 * account
	 *
	 * @param scid int SC account id
	 * @param bcid int BC account id
	 * @return void
	 */
	public function linkSCBCAccounts($scid = 0, $bcid = 0) {
		if(!is_numeric($scid) || $scid < 1) {
			throw new Exception("Invalid SC account ID");
		}
		if(!is_numeric($bcid) || $bcid < 1) {
			throw new Exception("Invalid BC account ID");
		}

		$scid = $this->_db->EscapeQueryStmt($scid);
		$bcid = $this->_db->EscapeQueryStmt($bcid);

		$sql = "UPDATE BigCommerceAccount SET AccId = {$scid} WHERE Id = {$bcid}";

		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
	}



	/*
	 * updateBCAccountActive
	 * Sets active flag to true
	 *
	 * @param bcid BC account ID
	 * @return void
	 */
	public function updateBCAccountActive($bcid = 0) {
		if(!is_numeric($bcid) || $bcid < 1) {
			throw new Exception("Invalid BC account ID");
		}
		$bcid = $this->_db->EscapeQueryStmt($bcid);
		$sql = "UPDATE BigCommerceAccount SET Active = 1 WHERE Id = {$bcid}";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
		}
	}



	/*
	 * accountLinked
	 * Checks to see if BC account is already associated
	 * with SC account
	 *
	 * @param hash string store hash
	 * @return true if accounts already linked
	 */
	public function accountLinked($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Store hash cannot be empty");
		}
		$hash = $this->_db->EscapeQueryStmt($hash);

		$sql = "SELECT COUNT(a.Id) AS TOT FROM BigCommerceAccount AS bca INNER JOIN Account AS a ON a.Id = bca.AccId WHERE bca.StoreHash = '{$hash}' AND a.Active = 1";
		if(!$this->_db->Query($sql)) {
			//TODO HANDLE
			return false;
		}

		$tot = $this->_db->GetRow();

		return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
	}




	/*
	 * getFullAccountReport
	 * Creates a simple report of the SC and BC account information
	 *
	 * @param scid int SC account ID
	 * @return array of data
	 */
	public function getFullAccountReport($scid = 0) {
		if(!is_numeric($scid) || $scid < 1) {
			throw new Exception("Invalid SC account ID");
		}
		$scid = $this->_db->EscapeQueryStmt($scid);

		$sql = "SELECT
				a.License AS License, a.Domain AS Domain, a.Name AS Name, a.Added AS TimeAdded,
				CASE WHEN bca.AuthComplete > 0 THEN 'yes' ELSE 'no' END AS AuthComplete,
				CASE WHEN bca.Active > 0 THEN 'yes' ELSE 'NO' END AS BCActive,
				COUNT(bcau.Id) AS TotUsers, 'Tyler Barnes' AS SupportRep, 'tylerb@conversionvoodoo.com' AS SupportEmail, '(619) 577-3861' AS SupportPhone
				FROM Account AS a
				INNER JOIN BigCommerceAccount AS bca
				ON bca.AccId = a.Id
				INNER JOIN BigCommerceAccountUser AS bcau
				ON bcau.BigCommerceAccountId = bca.Id
				WHERE a.Id = {$scid}";

		if(!$this->_db->Query($sql)) {
			return array();
		}
		return $this->_db->GetRow();
	}



	/*
	 * getLoadData
	 * Gets license, account name, and other information so the
	 * LOAD callback can display the install data
	 *
	 * @param hash string store hash
	 * @return array of data
	 */
	public function getLoadData($hash = "") {
		if(empty($hash) || strlen($hash) < 1) {
			throw new Exception("Cannot get LOAD data with empty store hash");
		}

		$hash = $this->_db->EscapeQueryStmt($hash);

		$sql = "SELECT a.License AS License, a.Domain AS Domain, t.Id AS ThemeId
				FROM Account AS a
				INNER JOIN Theme AS t
				ON t.AccId = a.Id
				INNER JOIN BigCommerceAccount AS bca
				ON bca.AccId = a.Id
				INNER JOIN BigCommerceAccountUser AS bcau
				ON bcau.BigCommerceAccountId = bca.Id
				WHERE bca.StoreHash = '{$hash}'";

		if(!$this->_db->Query($sql)) {
			// TODO HANDLE maybe put error code in array or something
			return array();
		}
		return $this->_db->GetRow();
	}




	/*
	 * setupDefaultTheme
	 * Sets up default theme with default values
	 *
	 * @param aid int sc account id
	 * @param name string theme name
	 * @param desc string theme description
	 * @return int new theme ID
	 */
	public function setupDefaultTheme($aid = 0, $name = "", $desc = "") {
		if(!is_numeric($aid) || $aid < 1) {
			throw new Exception("Cannot set up default theme with invalid account ID");
		}
		if(empty($name) || strlen($name) < 1) {
			throw new Exception("Cannot set up default theme with empty theme name");
		}
		if(empty($desc) || strlen($desc) < 1) {
			throw new Exception("Cannot set up default theme with empty theme description");
		}

		$aid = $this->_db->EscapeQueryStmt($aid);
		$name = $this->_db->EscapeQueryStmt($name);
		$desc = $this->_db->EscapeQueryStmt($desc);

		$sql = "INSERT INTO Theme (Id, AccId, Name, Description, Active, Del, Created) VALUES (NULL, {$aid}, '{$name}', '{$desc}', 1, 0, CURRENT_TIMESTAMP)";
		if(!$this->_db->Query($sql)) {
			// TODO HANDLE
			return 0;
		}

		return $this->_db->GetLastInsertedId();
	}



	/*
	 * setupDefaultNotifSidebar
	 * Sets up default sidebar
	 *
	 * @param aid int sc account ID
	 * @param name string sc account name
	 * @param img string image source
	 * @return int side bar id
	 */
	public function setupDefaultNotifSidebar($aid = 0, $name = "", $img = "") {
		if(!is_numeric($aid) || $aid < 1) {
			throw new Exception("Cannot set up default notification sidebar with invalid account ID");
		}
		if(empty($name) || strlen($name) < 1) {
			throw new Exception("Cannot set up default notification sidebar with empty name");
		}
		if(empty($img) || strlen($img) < 1) {
			throw new Exception("Cannot set up default notification sidebar with empty image src");
		}

		$aid = $this->_db->EscapeQueryStmt($aid);
		$name = $this->_db->EscapeQueryStmt($name);
		$img = $this->_db->EscapeQueryStmt($img);

		$sql = "INSERT INTO NotificationSideBar (Id, AccId, Title, Width, HeaderStyle, MainStyle, IconImg, ChatBubbleStyle, Active, Del) VALUES
				(NULL, {$aid}, '{$name}', NULL, NULL, NULL, '{$img}', NULL, 1, 0)";

		if(!$this->_db->Query($sql)) {
			// TODO HANDLE
			return 0;
		}

		return $this->_db->GetLastInsertedId();
	}



	/*
	 * linkThemeSideBar
	 * Links theme to sidebar
	 *
	 * @param themeid int theme ID
	 * @param sbid int sidebar ID
	 * @return void
	 */
	public function linkThemeSideBar($themeid = 0, $sbid = 0) {
		if(!is_numeric($themeid) || $themeid < 1) {
			throw new Exception("Cannot link theme - sidebar with invalid THEME ID");
		}
		if(!is_numeric($sbid) || $sbid < 1) {
			throw new Exception("Cannot link theme - sidebar with invalid SIDEBAR ID");
		}

		$themeid = $this->_db->EscapeQueryStmt($themeid);
		$sbid = $this->_db->EscapeQueryStmt($sbid);

		$sql = "INSERT INTO ThemeNotificationSideBar (Id, ThemeId, NotificationSideBarId) VALUES (NULL, {$themeid}, {$sbid})";
		if(!$this->_db->Query($sql)) {
			// TODO HANDLE
		}
		if(!$this->_db->GetLastInsertedId() < 1) {
			// TODO HANDLE
		}
	}



	/*
	 * setupDefaultNotificationElm
	 * Sets up default notif elm
	 *
	 * @param aid int sc account ID
	 * @param elmdata array element data
	 * @return int last inserted id
	 */
	public function setupDefaultNotificationElm($aid = 0, array $elmdata = array()) {
		if(!is_numeric($aid) || $aid < 1) {
			throw new Exception("Cannot set up notification elm with invalid account ID");
		}
		if(count($elmdata) < 1) {
			throw new Exception("Cannot set up notification elm with invalid element data");
		}

		$aid = $this->_db->EscapeQueryStmt($aid);
		foreach($elmdata as $index => $val) {
			$elmdata[$index] = $this->_db->EscapeQueryStmt($val);
		}

		$sql = "INSERT INTO NotificationElm (Id, TypeId, AccId, Name, ElmId, Height, Width, Style, DisplayOrder, InnerHtml, DisplayNotifCount, Active, Del) VALUES
				(NULL, (SELECT Id FROM NotificationElmType WHERE Type = '{$elmdata["type"]}'), {$aid}, '{$elmdata["name"]}', '{$elmdata["id"]}', '{$elmdata["h"]}',
				'{$elmdata["w"]}', NULL, '{$elmdata["display"]}', NULL, '{$elmdata["count"]}', 1, 0)";

		if(!$this->_db->Query($sql)) {
			// TODO HANDLE
			return 0;
		}

		return $this->_db->GetLastInsertedId();

	}



	/*
	 * addElementAttribute
	 * Adds an attribute and value to a notif elm
	 *
	 * @param elmid ibt elem ID
	 * @param attribute string attribute
	 * @param val string attribute value
	 * @return void
	 */
	public function addElementAttribute($elmid = 0, $attribute = "", $val = "") {
		if(!is_numeric($elmid) || $elmid < 1) {
			throw new Exception("Cannot set up notification elm attribute with invalid elm ID");
		}
		if(empty($attribute) || strlen($attribute) < 1) {
			throw new Exception("Cannot set up notification elm attribute with empty ATTRIBUTE");
		}
		if(empty($val) || strlen($val) < 1) {
			throw new Exception("Cannot set up notification elm attribute with empty attribute VALUE");
		}

		$elmid = $this->_db->EscapeQueryStmt($elmid);
		$attribute = $this->_db->EscapeQueryStmt($attribute);
		$val = $this->_db->EscapeQueryStmt($val);

		$sql = "INSERT INTO NotificationElmAttribute (Id, NotificationElmId, Attribute, Value, Active, Del) VALUES
				(NULL, {$elmid}, '{$attribute}', '{$val}', 1, 0)";

		if(!$this->_db->Query($sql)) {
			// TODO HANDLE
		}

	}



	/*
	 * linkThemeNotifElm
	 * Links theme to notification elm
	 *
	 * @param themeid int theme ID
	 * @param eid int elm ID
	 * @return void
	 */
	public function linkThemeNotifElm($themeid = 0, $eid = 0) {
		if(!is_numeric($themeid) || $themeid < 1) {
			throw new Exception("Cannot link element to theme with invalid THEME ID");
		}
		if(!is_numeric($eid) || $eid < 1) {
			throw new Exception("Cannot link element to theme with invalid ELM ID");
		}

		$themeid = $this->_db->EscapeQueryStmt($themeid);
		$eid = $this->_db->EscapeQueryStmt($eid);

		$sql = "INSERT INTO NotificationSet (Id, NotificationElmId, ThemeId) VALUES (NULL, {$eid}, {$themeid})";
		if(!$this->_db->Query($sql)) {
			// TODO HANDLE
		}
	}




}
?>
