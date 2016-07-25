<?php

/*
 * BigCommerceDefaultAccount
 * Sets up BC account with default stuff
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
class BigCommerceDefaultAccount extends BigCommerceAppAdmin {

	/*	PROPS	*/

	// account name
	public $_subAccountName;

	// theme
	private $_subThemeId;
	private $_subThemeName;
	private $_subThemeDescript;
	private $_subThemeCreated;

	// notification side bar
	private $_subNotifSideBarId;

	// ids for notification elements
	private $_subNotifElmIds = array();

	// notification element attribtes
	private $_subNotificationElmAttributes = array();

	// status
	private $_subDefaultsStatus;

	// STATICS
	public static $DEFAULT_THEME_NAME = "%s Theme";
	public static $DEFAULT_THEME_DESC = "Default SC theme for %s";
	public static $DEFAULT_IMG_SRC = "//d61fqxuabx4t4.cloudfront.net/elias-sc.jpg";
	public static $DEFAULT_NOTIF_ELMS = array(
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


	// CONSTANTS
	const SUB_DEFAULTS_FAILURE = -1;
	const SUB_DEFAULTS_SUCCESS = 1;



	/* END PROPS	*/


	/* PARENT CTOR	*/




	/*	ACCESSORS	*/


	public function getSubThemeId() { return $this->_subThemeId; }
	public function getSubThemeName() { return $this->_subThemeName; }
	public function getSubThemeDescript() { return $this->_subThemeDescript; }
	public function getSubNotifSideBarId() { return $this->_subNotifSideBarId; }
	public function getSubNotifElmIds() { return $this->_subNotifElmIds; }
	public function getSubDefaultsStatus() { return $this->_subDefaultsStatus; }

	public function subDefaultsStatusSuccess() { return ($this->_subDefaultsStatus === self::SUB_DEFAULTS_SUCCESS); }

	/*
	 * getSubNotificationElmAttributes
	 * Gets the value(s) for a/the attribute(s)
	 *
	 * @param attrib string attribute to get value for (optional, if omitted will return all attributes
	 * @return string value or array of attributes and values
	 */
	public function getSubNotificationElmAttributes($attrib = null) {
		if(!isset($attrib) || is_null($attrib)) {
			return $this->_subNotificationElmAttributes;
		}
		if(isset($this->_subNotificationElmAttributes[$attrib])) {
			return $this->_subNotificationElmAttributes[$attrib];
		}
		return false;
	}


	public function accountHasTheme() { return $this->_subThemeCreated; }


	public function setSubThemeId($id = 0) { $this->_subThemeId = $id; }
	public function setSubThemeName($name = "") { $this->_subThemeName = $name; }
	public function setSubThemeDescript($desc = "") { $this->_subThemeDescript = $desc; }
	public function setSubNotifSideBarId($id = 0) { $this->_subNotifSideBarId = $id; }
	public function setSubNotifElmIds(array $ids = array()) { $this->_subNotifElmIds = $ids; }
	public function setSubNotificationElmAttributes(array $data = array()) { $this->_subNotificationElmAttributes = $data; }
	private function setSubNotificationElmAttribute($attrib, $val) { $this->_subNotificationElmAttributes[$attrib] = $val; }

	public function setSubThemeCreated($bool = false) {
		$this->_subThemeCreated = $bool;
	}


	/*	END ACCESSORS	*/




	/*
	 * setup
	 * Sets up the parent property values and the values
	 * still needed here
	 *
	 * @return void
	 */
	public function setup() {

		$this->setRole("authSubmit");

		$this->setSubNotificationElmAttributes(array("src" => self::$DEFAULT_IMG_SRC, "class" => "sc-main-image"));

		$this->_subDefaultsStatus = null;
	}




	/*
	 * initDefaults
	 * Initializes setting up defaults
	 *
	 * @return void
	 */
	public function initDefaults() {
		$aid = $this->getSubSCAccountId();
		if(is_null($aid) || $aid < 1) {
			throw new Exception("Cannot set up defaults. Invalid SC account ID");
		}

		// check to see if theme and stuff already created
		$this->setSubThemeCreated($this->_dbAdapter->scAccountHasTheme($aid));

		$this->_subAccountName = $this->_dbAdapter->getSCAccountName($aid);
		// TODO Handle if accountName is empty

		$this->setSubThemeName(sprintf(self::$DEFAULT_THEME_NAME, $this->_subAccountName));
		$this->setSubThemeDescript(sprintf(self::$DEFAULT_THEME_DESC, $this->_subAccountName));

	}



	/*
	 * handleDefaults
	 * Handles setting up theme, notification sidebar, attributes,
	 * etc. as defaults for BC account
	 *
	 * @return void
	 */
	public function handleDefaults() {

		$processFailure = false;


		// only hand defaults if theme doesnt already exist
		if(!$this->accountHasTheme()) {

			// set up theme
			$this->setSubThemeId($this->_dbAdapter->setupDefaultTheme($this->getSubSCAccountId(), $this->getSubThemeName(), $this->getSubThemeDescript()));
			if($this->getSubThemeId() < 1) {
				$processFailure = true;
			}

			// set up notification sidebar
			$this->setSubNotifSideBarId($this->_dbAdapter->setupDefaultNotifSidebar($this->getSubSCAccountId(), $this->_subAccountName, self::$DEFAULT_IMG_SRC));
			if($this->getSubNotifSideBarId() < 1) {
				$processFailure = true;
			}

			// link theme and sidebar
			$this->_dbAdapter->linkThemeSideBar($this->getSubThemeId(), $this->getSubNotifSideBarId());

			// set up notification elements
			$defaultElms = self::$DEFAULT_NOTIF_ELMS;
			foreach($defaultElms as $idx => $elmdata) {
				try {

					$tmpElmId = $this->_dbAdapter->setupDefaultNotificationElm($this->getSubSCAccountId(), $elmdata);
					$this->_subNotifElmIds[] = $tmpElmId;
					if($elmdata["attribs"] === true) {

						foreach($this->_subNotificationElmAttributes as $attribute => $val) {
							try {
								$this->_dbAdapter->addElementAttribute($tmpElmId, $attribute, $val);
							} catch(Exception $e) {
								$processFailure = true;
							}
						}

					}


				} catch(Exception $e) {
					// TODO HANDLE
					$processFailure = true;
				}

			}

			// link notification elms to theme
			foreach($this->_subNotifElmIds as $eid) {

				try {
					$this->_dbAdapter->linkThemeNotifElm($this->getSubThemeId(), $eid);
				} catch(Exception $e) {
					$processFailure = true;
				}

			}


			if($processFailure) {
				$this->_subDefaultsStatus = self::SUB_DEFAULTS_FAILURE;
			} else {
				$this->_subDefaultsStatus = self::SUB_DEFAULTS_SUCCESS;
			}

		} else {
			$this->_subDefaultsStatus = self::SUB_DEFAULTS_SUCCESS;
		}


	}


}
?>
