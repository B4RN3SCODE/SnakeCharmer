<?php
include("config.php");
include("glob.php");
include("DBCon.php");
include("getDomain.php");


/************************************************************
 * getTheme
 * Gets theme data
 *
 * EXPECTS:
 *  - license (string license number)
 *  - theme (integer theme id to render)
 *
 * @author			Tyler J Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			0.0.0.0.1 (not even tested yet)
 * @doc				TBD
 *************************************************************/


// request origin -- url components for validation
$_ORIGIN_ = (isset($_SERVER["HTTP_REFERER"]) && validUrl($_SERVER["HTTP_REFERER"])) ? getDomain(parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST)) : STR_EMP;

// theme id
$_THEME_ = (isset($_REQUEST["theme"]) && is_numeric($_REQUEST["theme"])) ? $_REQUEST["theme"] : 0;
// license number
$_LICENSE_ = (isset($_REQUEST["license"]) && !empty($_REQUEST["license"]) && strlen($_REQUEST["license"]) > 0) ? $_REQUEST["license"] : STR_EMP;


if($_THEME_ < 1) {
	end_proc("window.SC.reportRequestFaulure('bad theme id');");
}

if(empty($_LICENSE_) || $_LICENSE_ == STR_EMP) {
	end_proc("window.SC.reportRequestFaulure('Bad License string');");
}

// database object
$db = new DBCon();
if(!$db->Link()) {
	end_proc("window.SC.reportRequestFaulure('Failed to connect to database');");
}

if(!validLicense($db, $_LICENSE_, $_ORIGIN_)) {
	file_put_contents("/var/www/logs/getTheme.log", "------\n\n{$_ORIGIN_}\n{$_LICENSE_}\n\n----\n\n", FILE_APPEND);
	end_proc("window.SC.reportInvalidLicense('Invalid Request');");
}


// skin data (theme to load)
$_SKIN_ = array();

// elm ids
$elm_ids = array();

// get the skin data
$tmp_skin = getThemeSkin($db, $_LICENSE_, $_THEME_);

$has_data = false;

foreach($tmp_skin as $idx => $elm_data) {

	$has_data = true;

	$elm_ids[] = (int)$elm_data["ElmRecordId"];
	unset($elm_data);

}

if(!$has_data) {
	end_proc("No Data");
}

// get attributes
$tmp_attr = getElmAttributes($db, $elm_ids);

$_SKIN_ = array(
	"elements"	=>	$tmp_skin,
	"attributes"	=>	$tmp_attr,
	"sidebar"	=>	getSideBarData($db, $_LICENSE_, $_THEME_),
);

// clean up
unset($tmp_attr, $tmp_skin, $elm_ids);

header("Content-Type: application/javascript");

// echo the results
echo "window.SC._themeData = " . json_encode($_SKIN_, JSON_NUMERIC_CHECK) . ";if(window.SC.setUpTheme()) { window.SC.getNotifData();} else { console.log('no theme fo you'); }";
// bye bye
exit;

//////////////////////////////////////////////////////////////
/////////////// functions ////////////////////////////////////
//////////////////////////////////////////////////////////////


function validLicense(DBCon $db, $lic, $dom) {

	$lic = $db->EscapeQueryStmt($lic);
	$dom = $db->EscapeQueryStmt($dom);

	$db->setQueryStmt("SELECT COUNT(*) AS tot FROM Account WHERE Domain = '{$dom}' AND License = '{$lic}'");
	$db->Query();
	$isValid = $db->GetRow();
	return (intval($isValid["tot"]) > 0);
}



/*
 * getThemeSkin
 * Gets the skin for all elements set up to render
 * for the given theme
 *
 * @param db (DBCon) Database object used to query
 * @param lic (string) the license number
 * @param thm (int) the theme id to query for
 * @return array of theme data
 */
function getThemeSkin(DBCon $db, $lic = "", $thm = 0) {

	$lic = $db->EscapeQueryStmt($lic);
	$thm = $db->EscapeQueryStmt($thm);

	$sql = "SELECT ne.Id AS ElmRecordId, ne.Name AS ElmName, ne.ElmId, ne.Height AS ElmH, ne.Width AS ElmW, ne.Style AS ElmStyle, ne.DisplayOrder AS ElmDO, ne.InnerHtml AS ElmInnerHtml, ne.DisplayNotifCount AS ElmShowCount,
				ns.ThemeId AS ThemeId,
				net.Type AS ElmType, net.HtmlTag AS ElmTag, net.CloseTag AS ElmUseCloseTag,
				a.Id AS AccountId
			FROM NotificationElm AS ne
			INNER JOIN NotificationSet AS ns
			ON ne.Id = ns.NotificationElmId
			INNER JOIN NotificationElmType AS net
			ON ne.TypeId = net.Id
			INNER JOIN Account AS a
			ON ne.AccId = a.Id
			INNER JOIN Theme AS t
			ON a.Id = t.AccId
			WHERE ne.Active = 1 AND ne.Del <> 1 AND net.Del <> 1 AND a.Active = 1 AND a.Del <> 1 AND ns.ThemeId = {$thm} AND a.License = '{$lic}'
			ORDER BY ne.DisplayOrder;";

	$db->setQueryStmt($sql);
	if(!$db->Query()) {
		// TODO HANDLE ERROR
		return array();
	}

	return $db->GetAll();
}


/*
 * getElmAttributes
 * Gets the attributes for all the elements required for the theme
 *
 * @param db (DBCon) database obj to use
 * @param elmIds (array) list of element record ids
 * @return array of the attributes
 */
function getElmAttributes(DBCon $db, array $elmIds = array("0")) {
	$str = implode(",",$elmIds);

	$sql = "SELECT nea.NotificationElmId AS ElmRecordId, nea.Attribute AS ElmAttribute, nea.Value AS ElmAttributeValue FROM NotificationElmAttribute AS nea WHERE nea.Active = 1 AND nea.Del <> 1 AND nea.NotificationElmId IN ({$str});";

	$db->setQueryStmt($sql);
	$db->Query();
	return $db->GetAll();
}



/*
 * getSideBarData
 * Gets side bar data
 *
 * @param db (DBCon) database obj to use
 * @param license string
 * @param theme id int
 * @return array of the sidebar data
 */
function getSideBarData(DBCon $db, $lic, $thm) {

	$lic = $db->EscapeQueryStmt($lic);
	$thm = $db->EscapeQueryStmt($thm);

	$sql = "SELECT
	nsb.Title AS SBTitle, nsb.IconImg AS SBImg, nsb.HeaderStyle, nsb.MainStyle, nsb.Width AS SBWidth, nsb.ChatBubbleStyle
		FROM NotificationSideBar AS nsb
		INNER JOIN Account AS a
		ON nsb.AccId = a.Id
		INNER JOIN ThemeNotificationSideBar AS tn
		ON nsb.Id = tn.NotificationSideBarId
		WHERE a.License = '{$lic}' AND tn.ThemeId = {$thm}";

	$db->setQueryStmt($sql);
	if(!$db->Query()) {
		// TODO HANDLE ERROR
		return array();
	}

	return $db->GetRow();
}




/*
 * validUrl
 * checks that param is valid url
 *
 * @param str: url
 * @return true if valid
 */
function validUrl($str = STR_EMP) {
	$str = trim(preg_replace("~http\:\/\/|https\:\/\/|www\.~", "", $str));
	return preg_match("~[-a-zA-Z0-9\:\%\.\_\+\~\#\=]{2,256}\.[a-z]{2,10}\b([-a-zA-Z0-9\:\%\_\+\.\~\#\?\&\/\/\=]*)~", $str);
}


/*
 * getDomain
 * gets the domain
 *
 * @param url to get domain from
 * @return string domain
 */
//function getDomain($url = STR_EMP) {
	//preg_match("/[a-z0-9\-]{1,63}\.[a-z\.]{2,10}$/", parse_url($url, PHP_URL_HOST), $_domain_tld);
	//return $_domain_tld[0];
//}



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
