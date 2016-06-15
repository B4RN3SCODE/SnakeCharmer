<?php
include_once("include/core/SCViewFactory.php");
/**************************************************
 * SCController
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/
class SCController {

	/**		PROPS	**/

	// module
	public $_module;
	// data
	public $_module_data;
	// view
	public $_view;
	// action
	public $_action;
	// view processor object
	public $_viewProcessor;
	// check for action
	private $_hasAction;

	/**		END PROPS		**/



	public function SCController(array $props_vals = array()) {
		$this->_viewProcessor = null;

		$this->setVars($props_vals);
		$this->_hasAction = (isset($this->_action) && !empty($this->_action) && strtolower($this->_action) != "index");
	}



	public function setModule($module = "") {
		$this->_module = $module;
	}



	public function setVars(array $arr = array()) {
		foreach($arr as $prop => $val) {
			if(property_exists($this, $prop)) {
				$this->$prop = $val;
			}
		}
	}



	public function Init() {
		$this->_viewProcessor = TSViewFactory::getView($this->_module, $this->_view);

		$this->Proc($this->_hasAction);
		return true;
	}

	public function Proc($forceIndexAction = false) {
		ob_start(); // start considering saving display stuff to buffer

		if($forceIndexAction) {
			$this->index();
		} else {
			$this->_viewProcessor->SetUp(); // not sure what this should do yet... maybe look for extra js files to include or something
			$this->{$this->_action}();
			// end output buffering shit in the view
		}
	}



	public function getModule() { return $this->_module; }
	public function getView() { return $this->_view; }
	public function getAction() { return $this->_action; }


}
?>
