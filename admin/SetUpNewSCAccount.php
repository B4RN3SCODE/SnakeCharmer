<?php
/*
 * SetUpNewSCAccount
 * Admin script to create a new account and set the
 * account up with the default theme/notification set.
 *
 * To be run via console and validates admin access
 */

/*	LOAD	*/
include_once("../include/admin.config.php");
include_once("../include/config.php");
include_once("../include/glob.php");
include_once("../include/DBCon.php");
/*	END LOAD	*/

global $_REQUIRED_ARGS, $AccountId, $ThemeId, $NotifSideBarId, $NotifElmIds, $AccountDomain, $AccountName, $DefaultThemeName, $DefaultThemeDesc, $DefaultImage, $DefaultNotificationElms, $NotificationElmAttributes, $DB;

/*	Required Arguments	*/
$_REQUIRED_ARGS = array("--DOMAIN", "--NAME", "--ADMINPWD");

/*	Global Variables	*/
// IDs
$AccountId = $ThemeId = $NotifSideBarId = 0;
$NotifElmIds = array();

// Account, Theme config
$AccountDomain = $AccountName = STR_EMP;
$DefaultThemeName = "%s Theme";
$DefaultThemeDesc = "Default SC theme for %s";
$DefaultImage = "//d61fqxuabx4t4.cloudfront.net/elias-sc.jpg";

// Notification Set config
$DefaultNotificationElms = array(
	0	=>	array(
			"type"		=>	"image",
			"name"		=>	"SC Main Image",
			"id"		=>	"imgSCMain",
			"display"	=>	2,
			"count"		=>	1,
			"h"			=>	"100px",
			"w"			=>	"100px",
			"attribs"	=>	true,
	),
	1	=>	array(
			"type"		=>	"paragraph",
			"name"		=>	"Simple Message Box",
			"id"		=>	"msgNotif",
			"display"	=>	1,
			"count"		=>	0,
			"h"			=>	"auto",
			"w"			=>	"300px",
			"attribs"	=>	false,
	),
);
$NotificationElmAttributes = array(
	"src"	=>	$DefaultImage,
	"class"	=>	"sc-main-image",
);

// db object
$DB = new DBCon();
/*	END Global Variables	*/

$proc_state = process_state($argv);
if($proc_state === INVALID_ARGS) {
	kill("Invalid arguments - Cannot create account", true);
} elseif($proc_state === DBCON_FAILURE) {
	kill("Failed to create DB connection", true);
} else {
	echo "Process begun...\n";
}

/*	CREATE ACCOUNT	*/
echo "...creating account...\n";
$AccountId = create_account();
if($AccountId < 1) {
	kill("Failed to create new account", true);
}
echo "done\n";


/*	SET UP THEME	*/
echo "...setting up theme...\n";
$ThemeId = setup_theme();
if($ThemeId < 1) {
	kill("Failed to set up theme", true);
}
echo "done\n";


/*	SET UP NOTIFICATION SIDE BAR	*/
echo "...setting up side bar...\n";
$NotifSideBarId = setup_notifsidebar();
if($NotifSideBarId < 1) {
	kill("Failed to set up notif side bar", true);
} else {
	echo "done\n";
	echo "linking theme and sidebar...\n";
	if(!link_theme_sidebar()) {
		kill("Failed to link theme and side bar... aborting", true);
	}
	echo "LINKED\n";
}
echo "done\n";


/*	SET UP NOTIFICATION ELEMENTS	*/
$NotifElmIds = setup_notifelms();
if(count($NotifElmIds) < 1) {
	kill("Failed to set up notifelms" . var_export($NotifElmIds, true) . "----", true);
} else {
	if(!link_theme_notifset()) {
		kill("Failed to link theme with notif set", true);
	} else {
		echo "******\tProcess FINISHED\nSummary:";
		$sum = "Account ID: {$AccountId}\nTheme ID: {$ThemeId}\nNotif Side Bar ID: {$NotifSideBarId}\nNotification Elm IDs: ";
		$sum .= var_export($NotifElmIds, true);
		$sum .= "\nLast Inserted NotificationSet: {$DB->GetLastInsertedId()}\n";
		echo $sum;
		kill("exit meow");
	}
}

/*	END PROCESS	*/

/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////

/*	FUNCTIONS	*/

/*
 * process_state
 * begins the process by validating arguments passed and
 * makes sure db connection is successful - returns state
 *
 * @param args array of arguments
 * @return int (1: ready, 0: db failure, -1: invalid args)
 */
function process_state($args = array()) {

	global $_REQUIRED_ARGS, $AccountDomain, $AccountName, $DB;


	$tmp = array_shift($args);
	unset($tmp);

	if(ADMIN_DEBUG_MODE === true) {
		echo "Notice: RUNNING IN DEBUG MODE\n(modify admin config to turn off)\n";
		$str = var_export($args, true);
		echo "-- args --\n"; echo $str;
		echo "\n---------------\n";
	}

	if(!isset($args) || count($args) != count($_REQUIRED_ARGS)) {
		return INVALID_ARGS;
	}

	$arg_vals = array();
	foreach($args as $idx => $arg) {
		$tmp = explode("=", $arg);
		if(isset($tmp[1]))
			$arg_vals[strtoupper($tmp[0])] = $tmp[1];
		else
			$arg_vals[strtoupper($tmp[0])] = null;
	}

	foreach($_REQUIRED_ARGS as $idx => $req_arg) {
		if(!isset($arg_vals[$req_arg]) || is_null($arg_vals[$req_arg]))
			return INVALID_ARGS;
	}

	if($arg_vals["--ADMINPWD"] != ADMIN_PWD)
		return INVALID_ARGS;

	$AccountDomain = $arg_vals["--DOMAIN"];
	$AccountName = $arg_vals["--NAME"];

	if(!$DB->Link()) {
		return DBCON_FAILURE;
	}

	return PROCESS_READY;
}



/*
 * create_account
 * creates an account and returns account id
 *
 * @return int account id
 */
function create_account() {

	global $AccountDomain, $AccountName, $DB;

	// verify account name and domain are set
	if(!isset($AccountDomain) || empty($AccountDomain) || !isset($AccountName) || empty($AccountName)) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "empty domain or name... name [{$AccountName}] - domain [{$AccountDomain}]\n";
		}
		return 0;
	}

	if(domain_exists($AccountDomain)) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Domain Already Exists {$AccountDomain}\n";
		}
		return 0;
	}

	$license_attempts = 0;
	$license_generation_failure = false;
	$license = generate_license();
	while(license_exists($license)) {
		$license_attempts++;
		$license = generate_license();
		if($license_attempts >= MAX_LIC_GENERATIONS) {
			if(ADMIN_DEBUG_MODE === true) {
				echo "Maximum license generation attempts reached\n";
			}
			$license_generation_failure = true;
			break;
		}
	}

	if($license_generation_failure) {
		return 0;
	}


	$sql = "INSERT INTO Account (Id, License, Domain, Name, Active, Del, Added) VALUES (NULL, '{$license}', '{$AccountDomain}', '{$AccountName}', 1, 0, CURRENT_TIMESTAMP)";
	if(!$DB->Query($sql)) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Insert error: {$DB->GetLastErrorMsg()}\n";
		}
		return 0;
	}

	return $DB->GetLastInsertedId();

}


/*
 * domain_exists
 * checks to see if account with domain exists
 *
 * @param domain string domain to check for
 * @return true if domain exists
 */
function domain_exists($domain) {
	global $DB;

	if(!isset($domain) || empty($domain) || is_null($domain)) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "domain not set or empty\n";
		}
		return true;
	}
	$domain = $DB->EscapeQueryStmt(strtolower($domain));
	$DB->Query("SELECT COUNT(*) AS TOT FROM Account WHERE Domain = '{$domain}' AND Del = 0");
	$tot = $DB->GetRow();
	return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
}



/*
 * license_exists
 * checks to see if license exists
 *
 * @param lic string license to check for
 * @return true if licesnse exists
 */
function license_exists($lic) {
	global $DB;

	if(!isset($lic) || empty($lic) || is_null($lic)) {
		return true;
	}
	$lic = $DB->EscapeQueryStmt($lic);
	$DB->Query("SELECT COUNT(*) AS TOT FROM Account WHERE License = '{$lic}'");
	$tot = $DB->GetRow();
	return (isset($tot["TOT"]) && intval($tot["TOT"]) > 0);
}



/*
 * generate_license
 * generates a randome 12 char string license
 *
 * @return string
 */
function generate_license() {
	$alpha_num = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$lng = strlen($alpha_num);
	$lic = "";
	for($i = 0; $i < LICENSE_LENGTH; $i++) {
		$lic .= $alpha_num[mt_rand(0,$lng-1)];
	}
	return $lic;
}



/*
 * setup_theme
 * sets theme up for account
 *
 * @return int theme id
 */
function setup_theme() {

	global $AccountId, $DefaultThemeName, $DefaultThemeDesc, $AccountName, $DB;

	if(!is_numeric($AccountId) || $AccountId < 1) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Invalid account id\n";
		}
		return 0;
	}

	$name = $DB->EscapeQueryStmt(sprintf($DefaultThemeName, $AccountName));
	$desc = $DB->EscapeQueryStmt(sprintf($DefaultThemeDesc, $AccountName));

	$sql = "INSERT INTO Theme (Id, AccId, Name, Description, Active, Del, Created) VALUES (NULL, {$AccountId}, '{$name}', '{$desc}', 1, 0, CURRENT_TIMESTAMP)";
	if(!$DB->Query($sql)) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Insert error: {$DB->GetLastErrorMsg()}\n";
		}
		return 0;
	}

	return $DB->GetLastInsertedId();

}




/*
 * setup_notifsidebar
 * sets up the side bar
 *
 * @return int id of the sidebar
 */
function setup_notifsidebar() {

	global $AccountId, $DB, $AccountName, $DefaultImage;

	if(!is_numeric($AccountId) || $AccountId < 1) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Invalid account id\n";
		}
		return 0;
	}

	$title = $DB->EscapeQueryStmt($AccountName);

	$sql = "INSERT INTO NotificationSideBar (Id, AccId, Title, Width, HeaderStyle, MainStyle, IconImg, ChatBubbleStyle, Active, Del) VALUES
			(NULL, {$AccountId}, '{$title}', NULL, NULL, NULL, '{$DefaultImage}', NULL, 1, 0)";

	if(!$DB->Query($sql)) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Insert error: {$DB->GetLastErrorMsg()}\n";
		}
		return 0;
	}

	return $DB->GetLastInsertedId();

}



/*
 * setup_notifelms
 * sets up notification elements
 *
 * @return array of ids
 */
function setup_notifelms() {

	global $AccountId, $DefaultNotificationElms, $DB;

	$ids = array();
	$insert_failure = false;
	if(!is_numeric($AccountId) || $AccountId < 1) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Invalid account id\n";
		}
		return $ids;
	}

	foreach($DefaultNotificationElms as $idx => $elmdata) {
		$sql = "INSERT INTO NotificationElm (Id, TypeId, AccId, Name, ElmId, Height, Width, Style, DisplayOrder, InnerHtml, DisplayNotifCount, Active, Del) VALUES
				(NULL, (SELECT Id FROM NotificationElmType WHERE Type = '{$elmdata["type"]}'), {$AccountId}, '{$elmdata["name"]}', '{$elmdata["id"]}', '{$elmdata["h"]}',
				'{$elmdata["w"]}', NULL, '{$elmdata["display"]}', NULL, '{$elmdata["count"]}', 1, 0)";
		if(!$DB->Query($sql)) {
			if(ADMIN_DEBUG_MODE === true) {
				echo "Elm Insert Failure: {$DB->GetLastErrorMsg()}\n";
			}
			$insert_failure = true;
		} else {
			$ids[] = $DB->GetLastInsertedId();
		}

		if($elmdata["attribs"] === true) {
			if(!add_elm_attributes($DB->GetLastInsertedId())) {
				echo "****Failed to add attribs for elm id: {$DB->GetLastInsertedId()}\n";
			}
		}
	}

	if($insert_failure) {
		$tmp = var_export($ids, true);
		echo "*****************\nExperienced Insert Failure!. Data:\n{$tmp}\n******************\n";
	}

	return $ids;
}



/*
 * add_elm_attributes
 * adds attributes to notif element
 *
 * @param elmid int element id
 * @return true if success
 */
function add_elm_attributes($elmid = 0) {
	global $NotificationElmAttributes, $DB;

	$failed = false;
	if($elmid < 1) {
		return false;
	}

	foreach($NotificationElmAttributes as $attribute => $val) {
		$sql = "INSERT INTO NotificationElmAttribute (Id, NotificationElmId, Attribute, Value, Active, Del) VALUES
				(NULL, {$elmid}, '{$attribute}', '{$val}', 1, 0)";
		if(!$DB->Query($sql)) {
			echo "Failed to add attribute ({$attribute}) to elm id {$elmid} [{$DB->GetLastErrorMsg()}]\n";
			$failed = true;
		}
	}

	return !$failed;
}



/*
 * link_theme_sidebar
 * links the them to the sidebar
 *
 * @return true if successful
 */
function link_theme_sidebar() {
	global $ThemeId, $NotifSideBarId, $DB;

	if(!is_numeric($ThemeId) || $ThemeId < 1 || !is_numeric($NotifSideBarId) || $NotifSideBarId < 0) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Invalid Id(s)\n";
		}
		return false;
	}

	if(!$DB->Query("INSERT INTO ThemeNotificationSideBar (Id, ThemeId, NotificationSideBarId) VALUES (NULL, {$ThemeId}, {$NotifSideBarId})")) {
		if(ADMIN_DEBUG_MODE === true) {
			echo "Insert error: {$DB->GetLastErrorMsg()}\n";
		}
		return false;
	}

	return true;
}



/*
 * link_theme_notifset
 * links theme to the notification set
 *
 * @return true on success
 */
function link_theme_notifset() {
	global $NotifElmIds, $DB, $ThemeId;

	$failed = false;
	foreach($NotifElmIds as $id) {
		$sql = "INSERT INTO NotificationSet (Id, NotificationElmId, ThemeId) VALUES (NULL, {$id}, {$ThemeId})";
		if(!$DB->Query($sql)) {
			echo "Failed to link theme with notif element {$ThemeId} -> {$id} :: {$DB->GetLastErrorMsg()}\n";
			$failed = true;
		}
	}
	return !$failed;
}



/*
 * kill
 * kills the process with a message
 *
 * @param msg string message to print
 * @param fatal bool true if fatal error
 * @return void
 */
function kill($msg = STR_EMP, $fatal = false) {
	if($fatal) {
		echo "**** FATAL ERROR:\n";
	}
	echo $msg . "\n";
	echo "............process ended\n";
	exit;
}
/*	END FUNCTIONS	*/

//////////////////////////////////////////////////////////////
///	END
//////////////////////////////////////////////////////////////
?>
