<?php
/**
 * ConversionVoodoo bootstrap
 *
 * @author Brian Gebel <briang@conversionvoodoo.com>
 * @author Tyler Barnes <tylerb@conversionvoodoo.com>
 * @link http://www.conversionvoodoo.com/
 * @copyright 2015 Conversion Voodoo
 */

/**
 * ConversionVoodoo is a helper file used to server all CV related functions
 * It encapsulates {@link Tracking} which provides the actual implementation.
 *
 */

// CTS root path
define('CTS_ROOT_PATH', dirname(__FILE__));

// Path definitions
define('CTS_LIBRARY_PATH', CTS_ROOT_PATH . DIRECTORY_SEPARATOR . 'lib');
define('CTS_TEST_PATH', CTS_ROOT_PATH . DIRECTORY_SEPARATOR . 'files');
define('CTS_THIRD_PARTY_PATH', CTS_ROOT_PATH . DIRECTORY_SEPARATOR . 'third_party');


/* SnakeCharmer MV ELM Options */
define('SC_MV_OPT_ON', 'on');
define('SC_MV_OPT_OFF', 'off');


// Load CTS classes
require(CTS_LIBRARY_PATH . DIRECTORY_SEPARATOR . 'ApiResource.php');
require(CTS_LIBRARY_PATH . DIRECTORY_SEPARATOR . 'Tracking.php');


// Load third party scripts
require(CTS_THIRD_PARTY_PATH . DIRECTORY_SEPARATOR . 'Mobile_Detect.php');



/////////////////////////////////////////////////////
// COMMON FUNCTIONS
/////////////////////////////////////////////////////

/**
 * Returns the site's base url (used for linking)
 * @param (void)
 * @return (string)
 */
function baseUrl($forceHttps = false)
{
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    return ($forceHttps) ? 'https://' . $_SERVER['HTTP_HOST'].'/' : $protocol . $_SERVER['HTTP_HOST'].'/';
}

/**
 * Returns the url to any static element or directory
 * @param (string) Path to file or folder
 * @return (string)
 */
function siteUrl($dir = '', $forceHttps = false)
{
	return baseurl($forceHttps) . "$dir";
}

/////////////////////////////////////////////////////
// THE END
/////////////////////////////////////////////////////
