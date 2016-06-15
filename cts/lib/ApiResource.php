<?php
/**
 * ConversionVoodoo bootstrap
 *
 * @author Brian Gebel <briang@conversionvoodoo.com>
 * @link http://www.conversionvoodoo.com/
 * @copyright 2015 Conversion Voodoo
 */

class CTS_ApiResource {

	// Static Variables
	public static $api_base = 'http://www.conversiontrackingsystem.com/api/';
	public static $cvo_cookie_domain;


	/**
	 * Gets contents from server
	 * @param: (string) - url to be called
	 * @param: (array)
	 * @param: (array)
	 * @return: (string)
	 */
	public static function get($file, $array, $other_array = array())
	{

		// Check if user is a bot if so don't make API call
		if(self::isBot())
			return false;

		// Build Query String
		$query_string = self::buildQueryString($array, $other_array);

		// Construct API URL
		$url = self::$api_base . $file . '.php' . $query_string;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch,CURLOPT_TIMEOUT, 3);
		$source = curl_exec($ch);
		curl_close($ch);

		return $source;
	}

	/**
	 * Retrieve get params
	 * @return: (mixed)
	 */
	public static function getParam($name) {
		return isset($_GET[$name]) ? $_GET[$name] : '';
	}

	/**
	 * Starts session on domain
	 * @return: (void)
	 */
	public static function startSession() {

		// Needed so that session is saved between http:// and https://www
		$cookie_domain = self::getCookieDomain();
		session_set_cookie_params(3600, '/', $cookie_domain, false);
		@session_start();
	}

	/**
	 * Get users cookie
	 * @return: (string) - returns null if now found
	 */
	public static function getCookie($name) {
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
	}

	/**
	 * Set users cookie
	 * @param: (string)
	 * @param: (mixed)
	 * @param: (int)
	 * @return: (void)
	 */
	public static function setCookie($name, $value, $expire=null) {
		if ($expire == null) $expire = time() + 60*60*24*30; // Default is 30 days from now
		$cookie_domain = self::getCookieDomain();
		setcookie($name, $value, $expire, '/', $cookie_domain);
	}

	/**
	 * Get cookie domain
	 * @param: (string)
	 * @return: (string)
	 */
	public static function getCookieDomain() {

		// Check cache
		if (empty(self::$cvo_cookie_domain)) {

			// Determine domain
			$host = $_SERVER['HTTP_HOST'];
			if (substr_count($host, '.') > 1) {
				$host = str_replace(substr($host, 0, strpos($host, '.')), '', $host);
			}

			// Save to cache variable
			self::$cvo_cookie_domain = $host;
		}
		return self::$cvo_cookie_domain;
	}

	////////////////////////////////////////////////////////////
	/// HELPER FUNCTIONS
	////////////////////////////////////////////////////////////

	/**
	 * Builds query string from array
	 * @param: (array) - array of paramaters allows any number of arrays
	 * @return: (string) - returns false if not array
	 */
	private static function buildQueryString( /* Array */ ) {

		// Get function arguments
		$args = func_get_args();

		// Get the number of arguments
		$arg_num = func_num_args();

		// Create query string
		$i = 0;
		$len = count($args);
		$buffer = '?';
		if ($arg_num > 0) {
			foreach ($args as $array) {
				if (is_array($array)) {
					$buffer .= http_build_query($array);
					if ($i < $len - 1) $buffer .= '&';
				}
				$i++;
			}
			return $buffer;
		}
		return false;
	}

	/**
	 * Checks if user is a bot
	 * @return: (bool)
	 */
	private static function isBot() {
		return (stristr($_SERVER['HTTP_USER_AGENT'], 'bot')!=''
		|| stristr($_SERVER['HTTP_USER_AGENT'], 'crawl')!=''
		|| stristr($_SERVER['HTTP_USER_AGENT'], 'spider')!='');
	}

	////////////////////////////////////////////////////////////
	/// THE END
	////////////////////////////////////////////////////////////

}
