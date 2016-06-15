<?php
/**
 * ConversionVoodoo bootstrap
 *
 * @author Brian Gebel <briang@conversionvoodoo.com>
 * @link http://www.conversionvoodoo.com/
 * @copyright 2015 Conversion Voodoo
 */

class CTS_Tracking extends CTS_ApiResource {

	// Private variable
	private $cvo_segment_id;
	private $cvo_test_id;
	private $cvo_other_vars;

	private $cvo_cookie_name;
	private $cvo_visitor_id;
	private $cvo_pageview_id;

	/**
	 * Construct object
	 * @param: (array)
	 * @return: (void)
	 */
	public function __construct($config = array()) {

		// Get options array
		$defaults = $this->constructorDefaults();

		// Merge arrays and override repeats
		$options = array_merge($defaults, $config);

		// Check test id is set
		if (is_numeric($options['test_id'])) {

			// Configure API
			$this->setTestId($options['test_id']);
			$this->setOtherVariables($options['other_vars']);
			$this->setSegmentId($options['segment_id']);

			// Start session
			$this->setVisitorCookieName($options['test_id']);
		}

		// Throw error
		else {
			throw new Exception("Invalid test id");
		}
	}

	/**
	 * Determines the file to be loaded
	 * Doesn't yet support multi page tests (see $next_page)
	 *
	 * @param: (string)
	 * @return: (string)
	 */
	public function split() {

		// Local Variables
		$test_id = $this->getTestId();
		$visitor_id = $this->getVisitorId();
		$segment_id = $this->getSegmentId();

		$ip = $_SERVER['REMOTE_ADDR'];
		$force = CTS_ApiResource::getParam('cvforce');
		$referer = isset($_SERVER['HTTP_REFERER']) ? urlencode($_SERVER['HTTP_REFERER']) : '';

		// Setup splitter params
		$params = array(
			'testid' => $test_id,
			'visitorid' => $visitor_id,
			'ip' => $ip,
			'force' => $force,
			'segmentid' => $segment_id,
			'referrerurl' => $referer
		);

		// Get other vars
		$other_vars = $this->getOtherVariables();

		// Call api for splitter
		if ($response = CTS_ApiResource::get('splitter', $params, $other_vars)) {

			// Initialize local variables
			list($new_visitorid, $action, $page_id) = explode('|', $response);

			// Set visitor cookie
			$this->setVisitorId($new_visitorid);

			// Will be in the form cv_index_eh.php=>cv_form_eh.php
			// Used for multi-page tests
			if (strpos($action, '=>') !== false) {
		        list($test_page, $next_page) = explode('=>', $action);
		    }
			else {
				$test_page = $action;
			}

			// Load variant
			if(!empty($test_page) && $test_page != 'STAY') {
				// if multi page, return array of funnel steps
				if(isset($next_page) && !empty($next_page) && strpos($next_page, '.php') !== false) {
					return array($test_page,$next_page);
				}
				return $test_page;
			}
			// Else do nothing
			return "STAY";
		}
		// Don't load variant
		return false;
	}

	/**
	 * Tracks current funnel
	 * @param: (int)
	 * @param: (array)
	 * @return: (string)
	 */
	public function track($page_id, $mvelement_ids = array()) {

		// Local Variables
		$mv_elements = array();
		$test_id = $this->getTestId();
		$visitor_id = $this->getVisitorId();


		$ip = $_SERVER['REMOTE_ADDR'];
		$mv_ids = implode(',', $mvelement_ids);
		$mv_force = CTS_ApiResource::getParam('cvmvforce');

		// Setup tracker params
		$params = array(
			'visitorid' => $visitor_id,
			'ip' => $ip,
			'pageid' => $page_id,
			'mvelementids' => $mv_ids,
			'mvforce' => $mv_force
			// orphanclues -- Still need to implement
		);
		// Call api for tracker
    	if ($response = CTS_ApiResource::get('tracker', $params)) {
    		// Seperate variables
			list($pageview_id, $mv_info, $new_visitorid) = explode('|', $response);

			// Set Page View id
			$this->setPageViewId($pageview_id);

			// Creates new cookie for Orphan visitor
			if (!$visitor_id) {

				// Set visitor cookie
				$this->setVisitorId($new_visitorid);
			}

			if(!empty($mvelement_ids))
			{

				//split multivarelements into elements
				$mv_pairs = explode(";;", $mv_info);
				if(count($mv_pairs))
				{
					foreach($mv_pairs as $mv_pair)
					{
						list($mv_name, $mv_val) = explode("::", $mv_pair);
						$mv_elements[$mv_name] = $mv_val;
					}
				}
			}
		}
		return $mv_elements;
	}

	/**
	 * Records a conversion for current funnel
	 * @param: (int) - Whether a conversion is a super conversion or not
	 * @param: (int) - Lead id (Reference id for customer)
	 * @param: (string) - Purchase description
	 * @param: (float) - Value of lead purchase
	 * @return: (mixed)
	 */
	public function converted($super_convert = 0, $lead_id = null, $buyer = null, $leadprice = null) {

		// Local Variables
		$test_id = $this->getTestId();
		$visitor_id = $this->getVisitorId();

		// Setup converted params
		$params = array(
			'testid' => $test_id,
			'visitorid' => $visitor_id,
			'super' => $super_convert,
			'leadid' => $lead_id,
			'buyer' => $buyer,
			'leadprice' => $leadprice
		);

		// Call api for converted
    	return CTS_ApiResource::get('converted', $params);
	}

	/**
	 * Records a variable for a specific visitor
	 * @param: (array) - Excepts array of key => value pairs
	 * @return: (mixed)
	 */
	public function variable($vars) {

		// Local Variables
		$test_id = $this->getTestId();
		$visitor_id = $this->getVisitorId();

		// Setup variables params
		$params = array(
			'testid' => $test_id,
			'visitorid' => $visitor_id
		);

		// Call api for variables
		return CTS_ApiResource::get('associatevariable', $params, $vars);
	}

	////////////////////////////////////////////////////////////
	/// VARIABLE ACCESS FUNCTIONS
	////////////////////////////////////////////////////////////

	/**
	 * Get segments
	 * @return: (void)
	 */
	public function getSegmentId() {
		return $this->cvo_segment_id;
	}

	/**
	 * Set segments
	 * @param: (int)
	 * @return: (void)
	 */
	public function setSegmentId($segment_id) {

		// Check segment_id is a number
		if (is_numeric($segment_id))
			return $this->cvo_segment_id = $segment_id;
		return false; // Variable is not a number
	}

	/**
	 * Get testid
	 * @return: (int)
	 */
	public function getTestId() {
		return $this->cvo_test_id;
	}

	/**
	 * Set testid
	 * @param: (int)
	 * @return: (bool)
	 */
	public function setTestId($test_id) {

		// Check testid is a number
		if (is_numeric($test_id))
			return $this->cvo_test_id = $test_id;
		return false; // Variable is not a number
	}

	/**
	 * Get other variables
	 * @return: (array)
	 */
	public function getOtherVariables() {
		return $this->cvo_other_vars;
	}

	/**
	 * Set other variables
	 * @param: (array)
	 * @return: (void)
	 */
	public function setOtherVariables($other_vars) {

		// Check other_vars is an array
		if (is_array($other_vars))
			return $this->cvo_other_vars = $other_vars;
		return false; // Variable is not an array
	}

	/**
	 * Get visitor cookie name
	 * @return: (int)
	 */
	public function getVisitorCookieName() {
		return $this->cvo_cookie_name;
	}

	/**
	 * Set visitor cookie name
	 * @param: (int)
	 * @return: (void)
	 */
	public function setVisitorCookieName($test_id) {

		// Set cookie name
		$this->cvo_cookie_name = "cvo_test_" . $test_id. "_visitorid";
	}

	/**
	 * Get previous visitorid from cookie
	 * @return: (int)
	 */
	public function getVisitorId() {

		// Check class variable first
		if (empty($this->cvo_visitor_id)) {

			// Get cookie value and also cache if avalible
			$this->cvo_visitor_id = CTS_ApiResource::getCookie($this->cvo_cookie_name);

		}
		return $this->cvo_visitor_id;
	}

	/**
	 * Store visitorid in cookie
	 * @param: (int)
	 * @param: (int)
	 * @return: (void)
	 */
	public function setVisitorId($visitor_id) {

		// Set class variable
		$this->cvo_visitor_id = $visitor_id;
		CTS_ApiResource::setCookie($this->cvo_cookie_name, $visitor_id);
	}

	/**
	 * Get page view id
	 * @return: (int)
	 */
	public function getPageViewId() {
		return $this->cvo_pageview_id;
	}

	/**
	 * Set page view id
	 * @param: (int)
	 * @return: (bool)
	 */
	public function setPageViewId($pageview_id) {

		// Check testid is a number
		if (is_numeric($pageview_id))
			return $this->cvo_pageview_id = $pageview_id;
		return false; // Variable is not a number
	}

	////////////////////////////////////////////////////////////
	/// HELPER FUNCTIONS
	////////////////////////////////////////////////////////////

	/**
	 * Defines inheritable function to expose constructor defaults
	 */
	protected function constructorDefaults() {
		return array (
			'test_id' => null, // Set to the test funnels id number (int)
			'segment_id' => null, // Set to the test funnels id number (int)
			'other_vars' => array() // Associative array with various variables to track when splitting
		);
	}

	////////////////////////////////////////////////////////////
	/// THE END
	////////////////////////////////////////////////////////////

}
