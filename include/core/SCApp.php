<?php
#region dependencies
include_once(INC_PTH . "DBCon.php");
include_once(INC_CORE_PTH . "SCAuthService.php");
include_once(INC_CORE_PTH . "SCControllerFactory.php");
#endregion
/**************************************************
 * SCApp
 *
 * Base class for the application.
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/

#retion SCApp
class SCApp {

	/**		Properties		**/

	// configuration (default mvc values - add more to config if needed)
	private $_config_;
	// debug mode - influences logging behavior and such
	private $_debug_;

	// authentication service
	public $_authService;
	// controller
	public $_controller;
	// dbcon instance
	public $_dbAdapter;

	// defaults (should get from config unless config is not passed)
	public $_defaultModule;
	public $_defaultView;
	public $_defaultAction;

	/**	STATIC	**/

	public static $DEFAULT_CONFIG_MAP = array(
		"module"	=>	array(
					"urii"		=>	0,
					"default"	=>	"home",
				),
		"data"		=>	array(
					"urii"		=>	1,
					"default"	=>	"",
				),
		"view"	=>	array(
					"urii"		=>	2,
					"default"	=>	"index",
				),
		"action"	=>	array(
					"urii"		=>	3,
					"default"	=>	"index",
				),
	);

	public static $SC_URI_PATH = "/sc/";

	/**		END PROPS		**/




	#region ctor

	/**************************************
	 * C'TOR
	 * Constructs an application object
	 *
	 * @param config array of config values
	 * @param debug bool true if run in debug mode
	 * @return void
	 ***************************************/
	public function SCApp(array $config = array(), $debug = false) {
		// debug mode or not
		$this->_debug_ = $debug;

		// check if config was passed
		if(!isset($config) || !is_array($config)) {
			$this->_config_ = $this->getDefaultConfig();
		} else {
			$this->_config_ = $config;
		}


		// store default prop vals
		$this->_defaultModule = $this->_config_["module"];
		$this->_defaultView = $this->_config_["view"];
		$this->_defaultAction = $this->_config_["action"];

		// auth service
		$this->_authService = null;
		// app controller
		$this->_controller = null;

		// database adapter
		$this->_dbAdapter = new DBCon();

	}

	#endregion





	public function Boot() {

		$this->_authService = new SCAuthService();
		if(!$this->_authService->validEntryPoint($_SERVER['REQUEST_URI'])) {
			// todo handle
		}


		if(!$this->_authService->isLoggedIn()) {
			// todo handle
		}

		$module = strtolower($this->Isolate(self::$DEFAULT_CONFIG_MAP["module"]["urii"]));

		$this->_controller = SCControllerFactory::getController($module);

		if($this->_controller === false) {
			// todo handle
		}

		$this->_controller->setModule($module);

		if(!$this->_dbAdapter->Link()) {
			if($this->_debug_) {
				echo "CANT ESTABLISH DB CONNECTION";
				exit;
			}

			/* TODO handle the database connection error in a way that makes sense  replacing the following line of code */
			die("Cant Run Right Now.... sorry dude.");
		}

		$this->Run();
	}






	private function Run() {

		$module_data = $this->Isolate(self::$DEFAULT_CONFIG_MAP["data"]["urii"]);
		if($module_data === false)
			$module_data = "d";

		$view = $this->Isolate(self::$DEFAULT_CONFIG_MAP["view"]["urii"]);
		if($view === false)
			$view = $this->_defaultView;

		$action = $this->Isolate(self::$DEFAULT_CONFIG_MAP["action"]["urii"]);
		if($action === false)
			$action = $this->_defaultAction;

		$this->_controller->setVars(array("_module_data"=>$module_data,"_view"=>$view,"_action"=>$action));

		$GLOBALS["APP"]["INSTANCE"] = $this;

		if($this->_controller->Init()) {

			$this->_controller->Exec();

			$this->CleanUp();

		} else {
			// todo handle
		}

	}





	/*************************************
	 * getDefaultconfig
	 * Gets default config
	 *
	 * @return array of config vals
	 **************************************/
	private function getDefaultConfig() {
		$ret = array();
		foreach(self::$DEFAULT_CONFIG_MAP as $item => $data) {
			$ret[$item] = $data["default"];
		}

		return $ret;
	}





	/************************************************
	 * Isolate
	 * Finds the module, view, action vars from the
	 * request URI
	 *
	 * @param uri_idx: int index number
	 * @return string value
	 *************************************************/
	private function Isolate($uri_idx) {
		$uri_cmpnts = explode("/", str_replace(self::$SC_URI_PATH, STR_EMP, $_SERVER['REQUEST_URI']));

		foreach($uri_cmpnts as $idx => $cmpnt)
			if(!self::StringHasValue($cmpnt) || is_null($cmpnt))
				unset($uri_cmpnts[$idx]);

		if(count($uri_cmpnts) > $uri_idx)
			return $uri_cmpnts[array_keys($uri_cmpnts)[$uri_idx]];
		else
			return false;
	}




	/*****************************************
	 * CleanUp
	 * clear resources and things
	 ****************************************/
	private function CleanUp() {
		return true;
	}


	/*********************************************
	 * SessionActivate
	 * starts a session
	 *
	 * @return bool true if success
	 **********************************************/
	public function SessionActivate() {
		if(isset($_SESSION["PHPSESSID"]) && $_SESSION["PHPSESSID"] == true && isset($_COOKIE["PHPSESSID"]) && !(is_null(session_id()))) {
			return false;
		}

		if(isset($_SESSION["PHPSESSID"])) unset($_SESSION["PHPSESSID"]);
		session_start();
		$_SESSION["PHPSESSID"] = true;
		return true;
	}

	/*********************************************
	 * SessionTerminate
	 * destroys a session
	 *********************************************/
	public function SessionTerminate() {
		if(isset($_SESSION["PHPSESSID"])) unset($_SESSION["PHPSESSID"]);
		session_destroy();
	}



	public function CookieBake($name = null, $value = null, $expr = 1, $pth = "/", $domain = null, $secr = null, $httpOnly = null) {
		if(!isset($name) || empty($name)) return false;
		$expr = (time() + (3600 * $expr));
		$domain = (is_null($domain)) ? ((isset($_SERVER["HTTP_HOST"])) ? $_SERVER["HTTP_HOST"] : BASE_PTH) : $domain;
		setcookie($name, $value, $expr, $pth, $domain, $secr, $httpOnly);
	}

	public function CookieBurn($name) {
		if(!isset($name) || empty($name)) return false;
		$this->CookieBake($name, "", -1);
	}

	public function BurnAllCookies() {
		foreach($_COOKIE as $name => $props) {
			$this->CookieBurn($name);
		}
	}

	public static function StringHasValue($str = STR_EMP) {
		if(!isset($str) || empty($str) || is_null($str))
			return false;

		$str = str_replace(array(" ", "\t", "\r", "\n"), "", $str);
		return (strlen($str) > 0 && !empty($str) && !is_null($str));
	}

}
#endregion

//////////////////////////////////////////////////////////////////////////////////
/// END
//////////////////////////////////////////////////////////////////////////////////

?>
