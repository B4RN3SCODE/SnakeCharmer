<?php
/*************************************
 * IAuthService
 *
 * @author			Tyler Barnes
 * @contact			b4rn3scode@gmail.com
 * @version			1.0
 ***************************************************/
/*+++++++++++++++++++++++++++++++++++++++++++++++++*
 * 				Change Log
 *
 *+++++++++++++++++++++++++++++++++++++++++++++++++*/
interface IAuthService {

	public function validEntryPoint($path);
	public function isLoggedIn();

}
?>
