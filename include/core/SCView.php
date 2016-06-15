<?php
/***************************************************
 * SCView
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/
class SCView {


	public $_pageTitle;

	public $_cssHrefs = array();

	public $_jsSrcs = array();

	public $_metaTags = array();

	public $_viewTpl;

	public $_htmlHead;

	public $_tplData;

	public $_displayOptions = array();

	public static $DEFAULT_OPTIONS = array("head" => true, "nav" => true, "foot" => true);



	public function SCView() {
		$this->_pageTitle = STR_EMP;
		$this->_cssHrefs = array();
		$this->_jsSrcs = array();
		$this->_metaTags = array();
		$this->_viewTpl = "";
		$this->_htmlHead = "";
		$this->_tplData = false;
		$this->_displayOptions = self::$DEFAULT_OPTIONS;
	}

	public function ViewExists($pth = STR_EMP) {
		if(SCApp::StringHasValue($pth)) {
			return file_exists($pth);
		}
		return file_exists($this->_viewTpl);
	}

	public function BuildHead() {
		if(!is_null($this->_metaTags) && !empty($this->_metaTags) && count($this->_metaTags) > 0) {
			foreach($this->_metaTags as $meta)
				$this->_htmlHead .= "<meta property=\"{$meta["property"]}\" content=\"{$meta["content"]}\" />";
		}
		if(!is_null($this->_jsSrcs) && !empty($this->_jsSrcs) && count($this->_jsSrcs) > 0) {
			foreach($this->_jsSrcs as $src)
				$this->_htmlHead .= "<script type=\"text/javascript\" src=\"{$src}\"></script>\n";
		}
		if(!is_null($this->_cssHrefs) && !empty($this->_cssHrefs) && count($this->_cssHrefs) > 0) {
			foreach($this->_cssHrefs as $href)
				$this->_htmlHead .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"${href}\">";
		}
	}

	public function SetUp() {
		return true;
	}

	public function LoadView() {
		$tmp = strtolower($GLOBALS["APP"]["INSTANCE"]->_controller->_module);
		$str_pth = "modules/{$GLOBALS["APP"]["MODULE_MAP"][$tmp]}/views/templates/{$this->_viewTpl}.php";

		if(!$this->ViewExists($str_pth)) {
			$errStr = "Tried to load a view template that does not exist: Path '{$this->_viewTpl}'";
			throw new Exception($errStr);
		} else {

			if(!isset($this->_pageTitle) || !TSApp::StringHasValue($this->_pageTitle))
				$this->_pageTitle = "Snake Charmer Web Portal";


			$PAGETITLE = $this->_pageTitle;
			$HTMLHEAD = $this->_htmlHead;


			if($this->_displayOptions["head"])
				include_once("views/head.php");
			if($this->_displayOptions["nav"])
				include_once("views/nav.php");

			$TPLDATA = $this->_tplData;

			if(TSApp::StringHasValue($this->_viewTpl)) {
				include_once($str_pth);
			}

			if($this->_displayOptions["foot"])
				include_once("views/foot.php");


			$viewData = ob_get_contents();
			return $viewData;
			ob_end_clean();


        }
	}





	public function setOptions($opts = array()) {
		if(count($opts) < 1)
			$this->_displayOptions = self::$DEFAULT_OPTIONS;
		else {
			foreach($opts as $key => $val) {
				if(!array_key_exists($key, self::$DEFAULT_OPTIONS))
					continue;

				$this->_displayOptions[$key] = $val;
			}
		}
		return (count($this->_displayOptions) == count(self::$DEFAULT_OPTIONS));
	}



}
?>