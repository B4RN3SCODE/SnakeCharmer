<?php
include_once("include/core/IAuthService.php");

/***********************************************
 * SCAuthService
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/
class SCAuthService implements IAuthService {

	public function SCAuthService() { }

	public function validEntryPoint($path) {
		return true;
	}

	public function isLoggedIn() {
		return true;
	}
}
?>
