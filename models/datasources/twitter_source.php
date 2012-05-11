<?php

App::import('Core', array('Xml', 'Cache'));
App::import('vendor', 'TwitterKit.HttpSocketOauth', array('file' => 'http_socket_oauth' . DS . 'http_socket_oauth.php'));

/**
 * Twitter API Datasource
 *
 * for CakePHP 1.3+
 * PHP version 5.2+
 *
 * Copyright 2010, ELASTIC Consultants Inc. (http://elasticconsultants.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.0
 * @filesource
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010, ELASTIC Consultants Inc.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.models.datasources
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 *
 * @see http://apiwiki.twitter.com/Twitter-API-Documentation
 *
 * This Class use HttpSocketOAuth:
 *
 *   Neil Crookes » OAuth extension to CakePHP HttpSocket
 *     http://www.neilcrookes.com/2010/04/12/cakephp-oauth-extension-to-httpsocket/
 *     http://github.com/neilcrookes/http_socket_oauth
 *
 * Thank you.
 */

/**
 * @property HttpSocketOauth $Http
 */
class TwitterSource extends DataSource {

	public $description = 'Twitter API';

/**
 *
 * @var string
 */
	public $oauth_consumer_key;

/**
 *
 * @var string
 */
	public $oauth_consumer_secret;

/**
 *
 * @var string
 */
	public $oauth_callback;

/**
 *
 * @var string
 */
	public $oauth_token;

/**
 *
 * @var string
 */
	public $oauth_token_secret;

	const HTTP_URL = 'http://twitter.com/';

	const HTTPS_URL = 'https://twitter.com/';

	const TWITTER_API_URL_BASE = 'http://api.twitter.com/';

	const TWITTER_API_URL_BASE_HTTPS = 'https://api.twitter.com/';

	const ANYWHERE_IDENTITY = 'twitter_anywhere_identity';

/**
 *
 * @var array
 */
	public $_baseConfig = array(
		'oauth_consumer_key' => '',
		'oauth_consumer_secret' => '',
		'oauth_token' => '',
		'oauth_token_secret' => '',
		'oauth_callback' => '',
		'cache' => false,
		'refresh_cache' => false,
	);

/**
 *
 * @param array $config
 */
	public function __construct($config) {
		parent::__construct($config);
		$this->Http = & new HttpSocketOauth();
		$this->reset();
	}

/**
 * Reset object vars
 */
	public function reset() {
		$this->oauth_consumer_key = $this->config['oauth_consumer_key'];
		$this->oauth_consumer_secret = $this->config['oauth_consumer_secret'];
		$this->oauth_token = $this->config['oauth_token'];
		$this->oauth_token_secret = $this->config['oauth_token_secret'];
		$this->oauth_callback = $this->config['oauth_callback'];
	}

/**
 * set OAuth Token
 *
 * @param mixed  $token
 * @param string $secret
 * @return ture|false
 */
	public function setToken($token, $secret = null) {
		if (is_array($token) && !empty($token['oauth_token']) && !empty($token['oauth_token_secret'])) {
			$this->oauth_token = $token['oauth_token'];
			$this->oauth_token_secret = $token['oauth_token_secret'];
			return true;
		} else if (!empty($token) && !empty($secret)) {
			$this->oauth_token = $token;
			$this->oauth_token_secret = $secret;
			return true;
		}

		return false;
	}

/**
 * Enable Cache
 *
 * @params mixed $config
 */
	public function enableCache($config = true) {
		$this->setConfig(array('cache' => $config));
	}

/**
 * Next request force update cache
 */
	public function refreshCache() {
		$this->setConfig(array('refresh_cache' => true));
	}

/**
 * Request API and process responce
 *
 * @param array $params
 * @param bool  $is_process
 * @return mixed
 */
	protected function _request($params, $is_process = true) {
		$this->_setupCache();

		if ($this->_cacheable($params) && !$this->config['refresh_cache']) {
			// get Cache, only GET method
			$response = Cache::read($this->_getCacheKey($params), $this->configKeyName);
		}

		if (empty($response)) {
			$response = $this->Http->request($params);

			if ($this->_cacheable($params)) {
				// save Cache, only GET method
				$cache = Cache::write($this->_getCacheKey($params), $response, $this->configKeyName);
				$this->config['refresh_cache'] = false;
			}
		}

		if ($is_process) {
			$response = json_decode($response, true);
		}

		// -- error logging
		if ($is_process && !empty($response['error'])) {
			$this->log($response['error'] . "\n" . print_r($params, true), LOG_DEBUG);
		}

		return $response;
	}

/**
 * get Cache key
 *
 * @param array $params
 * @return stirng
 */
	protected function _getCacheKey($params) {
		return sha1($this->oauth_token . serialize($params));
	}

/**
 *
 */
	protected function _setupCache() {
		if ($this->config['cache'] && !Cache::isInitialized($this->configKeyName)) {
			if (!is_array($this->config['cache'])) {
				$this->config['cache'] = array(
					'engine' => 'File',
					'duration' => '+5 min',
					'path' => CACHE . 'twitter' . DS,
					'prefix' => 'cake_' . Inflector::underscore($this->configKeyName) . '_',
				);
			}
			Cache::config($this->configKeyName, $this->config['cache']);
		}
	}

/**
 * is cacheable
 *
 * @param array $params
 * @return bool
 */
	protected function _cacheable($params) {
		return $this->config['cache'] && strtoupper($params['method']) == 'GET' && !preg_match('!/oauth/!i', $params['uri']['path']);
	}

/**
 * Build request array
 *
 * @param string $url
 * @param string $method
 * @param array  $body   GET: query string POST: post data
 * @return array
 */
	protected function _buildRequest($url, $method = 'GET', $body = array()) {
		$method = strtoupper($method);

		// extract path
		if (!preg_match('!^http!', $url)) {
			$url = self::TWITTER_API_URL_BASE_HTTPS . $url;
		}

		$uri = parse_url($url);

		// add GET params
		if (!empty($body) && $method == 'GET') {
			if (empty($uri['query'])) {
				$uri['query'] = array();
			}
			$uri['query'] = array_merge($uri['query'], $body);
			$body = array();
		}

		$params = compact('uri', 'method', 'body');

		// -- Set Auth parameter
		if (!empty($this->oauth_consumer_key) && !empty($this->oauth_consumer_secret)) {
			// OAuth
			$params['auth']['method'] = 'OAuth';
			$params['auth']['oauth_consumer_key'] = $this->oauth_consumer_key;
			$params['auth']['oauth_consumer_secret'] = $this->oauth_consumer_secret;

			if (!empty($this->oauth_token) && !empty($this->oauth_token_secret)) {
				$params['auth']['oauth_token'] = $this->oauth_token;
				$params['auth']['oauth_token_secret'] = $this->oauth_token_secret;
			}
		}

		return $params;
	}

/**
 * for DebugKit call
 */
	public function getLog() {
		return array('log' => array(), 'count' => array(), 'time' => array());
	}

/**
 * check Xml response
 *
 * @param  string $src
 * @return true|false
 */
	protected function _isXml($src) {
		return preg_match('!^<\?xml!', $src);
	}

/**
 * get Error Message
 *
 * @param string $src
 * @param string
 */
	protected function _getOAuthError($src) {
		$xml = new Xml($src);
		$result = $xml->toArray();
		return !empty($result['Hash']['error']) ? $result['Hash']['error'] : 'Error';
	}

	// ====================================================
	// == API Limit Methods
	// ====================================================

/**
 * get API remaining
 *
 * @return integer|null
 */
	public function getRatelimitRemaining() {
		return !empty($this->Http->response['header']['X-Ratelimit-Remaining']) ? intval($this->Http->response['header']['X-Ratelimit-Remaining']) : null;
	}

/**
 * get API limit
 *
 * @return integer|null
 */
	public function getRatelimitLimit() {
		return !empty($this->Http->response['header']['X-Ratelimit-Limit']) ? intval($this->Http->response['header']['X-Ratelimit-Limit']) : null;
	}

/**
 * get API limit reset time
 *
 * @return integer|null
 */
	public function getRatelimitReset() {
		return !empty($this->Http->response['header']['X-Ratelimit-Reset']) ? intval($this->Http->response['header']['X-Ratelimit-Reset']) : null;
	}

/**
 * get Ratelimit Class
 *
 * @return string|null
 */
	public function getRatelimitClass() {
		return !empty($this->Http->response['header']['X-Ratelimit-Class']) ? $this->Http->response['header']['X-Ratelimit-Class'] : null;
	}

/**
 * get API remaining (Search API)
 *
 * @return integer|null
 */
	public function getFeatureRatelimitRemaining() {
		return !empty($this->Http->response['header']['X-FeatureRateLimit-Remaining']) ? intval($this->Http->response['header']['X-FeatureRateLimit-Remaining']) : null;
	}

/**
 * get API limit (Search API)
 *
 * @return integer|null
 */
	public function getFeatureRatelimitLimit() {
		return !empty($this->Http->response['header']['X-FeatureRateLimit-Limit']) ? intval($this->Http->response['header']['X-FeatureRateLimit-Limit']) : null;
	}

/**
 * get API limit reset time (Search API)
 *
 * @return integer|null
 */
	public function getFeatureRatelimitReset() {
		return !empty($this->Http->response['header']['X-FeatureRateLimit-Reset']) ? intval($this->Http->response['header']['X-FeatureRateLimit-Reset']) : null;
	}

	// ====================================================
	// == Search API Methods
	// ====================================================

/**
 * search
 *
 * @param array  $params
 *     lang:        Optional: Restricts tweets to the given language, given by an ISO 639-1 code.
 *     locale:      Specify the language of the query you are sending (only ja is currently effective).
 *                            This is intended for language-specific clients and the default should work in the majority of cases.
 *     max_id:      Returns tweets with status ids less than the given id.
 *     q:           The text to search for.  See the example queries section for examples of the syntax supported in this parameter
 *     rpp:         The number of tweets to return per page, up to a max of 100.
 *     page:        The page number (starting at 1) to return, up to a max of roughly 1500 results (based on rpp * page: Note: there are pagination limits.
 *     since:       Returns tweets with since the given date.  Date should be formatted as YYYY-MM-DD
 *     since_id:    Returns tweets with status ids greater than the given id.
 *     geocode:     Returns tweets by users located within a given radius of the given latitude/longitude.
 *                            The location is preferentially taking from the Geotagging API, but will fall back to their Twitter profile.
 *                            The parameter value is specified by "latitide,longitude,radius", where radius units must be specified as either "mi" (miles) or "km" (kilometers).
 *                            Note that you cannot use the near operator via the API to geocode arbitrary locations; however you can use this geocode parameter to search near geocodes directly.
 *     show_user:   When true, prepends "<user>:" to the beginning of the tweet. This is useful for readers that do not display Atom's author field. The default is false.
 *     until:       Returns tweets with generated before the given date.  Date should be formatted as YYYY-MM-DD
 *     result_type: Specifies what type of search results you would prefer to receive.
 *         o Valid values include:
 *             + mixed: In a future release this will become the default value. Include both popular and real time results in the response.
 *             + recent: The current default value. Return only the most recent results in the response.
 *             + popular: Return only the most popular results in the response.
 *
 * @return array|false
 * @see http://apiwiki.twitter.com/Twitter-Search-API-Method%3A-search
 */
	public function search($params = array()) {
		$url = 'http://search.twitter.com/search.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('q' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET trends
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/trends
 */
	public function trends($params = array()) {
		$url = 'http://search.twitter.com/trends.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('q' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET trends/current
 *
 * @param array  $params
 *  *Optional*
 *      exclude: Setting this equal to hashtags will remove all hashtags from the trends list.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/trends/current
 */
	public function trends_current($params = array()) {
		$url = 'http://search.twitter.com/trends/current.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('exclude' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET trends/daily
 *
 * @param array  $params
 *  *Optional*
 *      exclude: Setting this equal to hashtags will remove all hashtags from the trends list.
 *      date:    Permits specifying a start date for the report. The date should be formatted YYYY-MM-DD.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/trends/daily
 */
	public function trends_daily($params = array()) {
		$url = 'http://search.twitter.com/trends/daily.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('date' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET trends/weekly
 *
 * @param array  $params
 *  *Optional*
 *      exclude: Setting this equal to hashtags will remove all hashtags from the trends list.
 *      date:    Permits specifying a start date for the report. The date should be formatted YYYY-MM-DD.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/trends/weekly
 */
	public function trends_weekly($params = array()) {
		$url = 'http://search.twitter.com/trends/weekly.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('date' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Timeline Methods
	// ====================================================

/**
 * GET statuses/home_timeline
 *
 * @param array  $params
 *  *Optional*
 *     since_id: Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:   Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:    Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:     Specifies the page of results to retrieve. Note: there are pagination limits.
 *     skip_user:
 *     include_entities:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/home_timeline
 */
	public function statuses_home_timeline($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/home_timeline.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/user_timeline
 *
 * @param array  $params
 *  *Optional*
 *     id:          Specifies the ID or screen name of the user for whom to return the user_timeline.
 *     user_id:     Specfies the ID of the user for whom to return the user_timeline. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *     screen_name. Specfies the screen name of the user for whom to return the user_timeline. Helpful for disambiguating when a valid screen name is also a user ID.
 *     since_id:    Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:      Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:       Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:        Specifies the page of results to retrieve. Note: there are pagination limits.
 *     skip_user:
 *     include_rts:
 *     include_entities:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/user_timeline
 */
	public function statuses_user_timeline($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/user_timeline.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/mentions
 *
 * @param array  $params
 *  *Optional*
 *     since_id:  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:    Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:     Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:      Specifies the page of results to retrieve. Note: there are pagination limits.
 *     include_rts:
 *     include_entities:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/mentions
 */
	public function statuses_mentions($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/mentions.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/retweeted_by_me
 *
 * @param array  $params
 *  *Optional*
 *     since_id:  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:    Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:     Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:      Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/retweeted_by_me
 */
	public function statuses_retweeted_by_me($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/retweeted_by_me.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/retweeted_to_me
 *
 * @param array  $params
 *  *Optional*
 *     since_id:  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:    Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:     Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:      Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/retweeted_to_me
 */
	public function statuses_retweeted_to_me($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/retweeted_to_me.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/retweets_of_me
 *
 * @param array  $params
 *  *Optional*
 *     since_id:  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:    Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:     Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:      Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/retweets_of_me
 */
	public function statuses_retweets_of_me($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/retweets_of_me.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Status Methods
	// ====================================================

/**
 * GET statuses/show
 *
 * @param array  $params
 *  *Required*
 *      id: The numerical ID of the desired status.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/show
 */
	public function statuses_show($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/show.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST statuses/update
 *
 * @param array  $params
 *  *Required*
 *      status: The text of your status update, up to 140 characters. URL encode as necessary.
 *  *Optional*
 *      in_reply_to_status_id: The ID of an existing status that the update is in reply to.
 *      lat:  The location's latitude that this tweet refers to.
 *      long: The location's longitude that this tweet refers to.
 *      place_id: A place in the world. These IDs can be retrieved from geo/reverse_geocode.
 *      display_coordinates: Whether or not to put a pin on the exact coordinates a tweet has been sent from.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/statuses/update
 */
	public function statuses_update($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/update.json';
		$method = 'POST';

		if (is_scalar($params)) {
			$params = array('status' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST statuses/destroy
 *
 * @param string $id
 * @param array  $params
 *  *Required*
 *      id: The numerical ID of the desired status.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/statuses/destroy
 */
	public function statuses_destroy($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/destroy/%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST statuses/retweet/:id
 *
 * @param string $id *Required* The numerical ID of the tweet you are retweeting.
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/statuses/retweet/:id
 */
	public function statuses_retweet($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/retweet/%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/retweets
 *
 * @param array  $params
 *  *Required*
 *      id: The numerical ID of the tweet you want the retweets of.
 *  *Optional*
 *      count: Specifies the number of retweets to retrieve. May not be greater than 100.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/retweets
 */
	public function statuses_retweets($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/retweets.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/:id/retweeted_by
 *
 * @param string $id *Required* The id of the status
 * @param array  $params
 *  *Optional*
 *      count:  Indicates number of retweeters to return per page, with a maximum 100 possible results.
 *      page:   Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/:id/retweeted_by
 */
	public function get_statuses_id_retweet_by($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/%s/retweeted_by.json', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET statuses/:id/retweeted_by/ids
 *
 * @param string $id *Required* The id of the status
 * @param array  $params
 *  *Optional*
 *      count:  Indicates number of retweeters to return per page, with a maximum 100 possible results.
 *      page:   Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/statuses/:id/retweeted_by/ids
 */
	public function get_statuses_id_retweeted_by_ids($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/statuses/%s/retweeted_by/ids.josn', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == User Methods
	// ====================================================

/**
 * GET users/show
 *
 * @param array  $params
 *  *Optional*
 *      id:          The ID or screen name of a user.
 *      user_id:     Specfies the ID of the user to return. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name: Specfies the screen name of the user to return. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/users/show
 */
	public function users_show($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/users/show.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET users/lookup
 *
 * @param array  $params
 *  *Optional*
 *      user_id:     Specfies the ID of the user to return.
 *      screen_name: Specfies the screen name of the user to return.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/users/lookup
 */
	public function users_lookup($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/users/lookup.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET users/search
 *
 * @param array  $params
 * *Required*
 *      q:         The query to run against people search.
 * *Optional*
 *      per_page:  Specifies the number of statuses to retrieve. May not be greater than 20.
 *      page:      Specifies the page of results to retrieve.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/users/search
 */
	public function users_search($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/users/search.json';
		$method = 'GET';

		if (is_scalar($params)) {
			$params = array('q' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET users/suggestions
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/users/suggestions
 */
	public function users_suggestions($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/users/suggestions.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET users/suggestions/slug
 *
 * @param string $slug *Required* The short name of list or a category
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/users/suggestions/slug
 */
	public function users_suggestions_category($slug, $params = array()) {
		if (empty($slug)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/users/suggestions/%s.json', $slug);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == List Methods
	// ====================================================

/**
 * POST :user/lists (create)
 *
 * @param string $user
 * @param array  $params
 *  *Required*
 *      name:         The name of the list you are creating.
 *  *Optional*
 *      mode:         Whether your list is public or private. Values can be public or private.
 *      description:  A description of the user owning the account. Maximum of 160 characters.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/:user/lists
 * @deprecated
 */
	public function post_lists($user, $params = array()) {
		if (empty($user)) {
			return false;
		}

		return $this->lists_create($params);
	}

/**
 * POST lists/create
 *
 * @param array  $params
 *  *Required*
 *      name:         The name of the list you are creating.
 *  *Optional*
 *      mode:         Whether your list is public or private. Values can be public or private.
 *      description:  A description of the user owning the account. Maximum of 160 characters.
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/create
 * @deprecated
 */
	public function lists_create($params) {
		if (empty($params['name'])) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/create.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST :user/lists/:id (update)
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      name:         Full name associated with the profile. Maximum of 20 characters.
 *      mode:         Whether your list is public or private. Values can be public or private.
 *      description:  A description of the user owning the account. Maximum of 160 characters.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/:user/lists/:id
 * @deprecated
 */
	public function post_lists_id($user, $list_id, $params = array()) {
		if (empty($user) || empty($id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		// request
		return $this->lists_update($params);
	}

/**
 * POST lists/update
 *
 * @param array  $params
 *		list_id:
 *		slug:
 *      name:         Full name associated with the profile. Maximum of 20 characters.
 *      mode:         Whether your list is public or private. Values can be public or private.
 *      description:  A description of the user owning the account. Maximum of 160 characters.
 *		owner_screen_name:
 *		owner_id:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/update
 */
	public function lists_update($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/update.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/lists (index)
 *
 * @param string $user
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/lists
 * @deprecated
 */
	public function get_lists($user, $params = array()) {
		if (empty($user)) {
			return false;
		}

		if (!is_numeric($user)) {
			$params['screen_name'] = $user;
		} else {
			$params['user_id'] = $user;
		}

		// request
		return $this->lists($params);
	}

/**
 * GET lists
 *
 * @param array  $params
 *		user_id:
 *      screen_name:
 *      cursor:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists
 * @deprecated
 */
	public function lists($params) {
		if (empty($params['user_id']) && empty($params['screen_name'])) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/lists/:id (show)
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      id: The id or slug of the list.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/lists/:id
 * @deprecated
 */
	public function get_lists_id($user, $list_id, $params = array()) {
		if (empty($user) || empty($id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		return $this->lists_show($params);
	}

/**
 * GET lists/show
 *
 * @param string $user
 * @param string $id
 * @param array  $params
 *		list_id:
 *		slug:
 *		owner_screen_name:
 *		owner_id:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/show
 */
	public function lists_show($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/show.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * DELETE :user/lists/:id (destroy)
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      id: The id or slug of the list.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/delete/:user/lists/:id
 * @deprecated
 */
	public function delete_lists_id($user, $list_id, $params = array()) {
		if (empty($user) || empty($id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		// request
		return $this->lists_destory($params);
	}

/**
 * POST lists/destroy
 *
 * @param array  $params
 *		list_id:
 *		slug:
 *		owner_screen_name:
 *		owner_id:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/destroy
 */
	public function lists_destory($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/destroy.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/lists/:id/statuses
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      since_id: Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *      max_id:   Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *      per_page: Specifies the number of statuses to retrieve. May not be greater than 200.
 *      page:     Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/lists/:id/statuses
 * @deprecated
 */
	public function get_lists_statuses($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		return $this->lists_statuses($params);
	}

/**
 * GET lists/statuses
 *
 * @param array  $params
 *		list_id:
 *		slig:
 *		owner_screen_name:
 *		owner_id:
 *      since_id: Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *      max_id:   Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *      per_page: Specifies the number of statuses to retrieve. May not be greater than 200.
 *      page:     Specifies the page of results to retrieve. Note: there are pagination limits.
 *		include_entities:
 *		include_rts:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/statuses
 */
	public function lists_statuses($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/statuses.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/lists/memberships
 *
 * @param string $user
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/lists/memberships
 * @deprecated
 */
	public function get_lists_memberships($user, $params = array()) {
		if (empty($user)) {
			return false;
		}

		if (!is_numeric($user)) {
			$params['screen_name'] = $user;
		} else {
			$params['user_id'] = $user;
		}

		return $this->lists_memberships($params);
	}

/**
 * GET lists/memberships
 *
 * @param array  $params
 *		user_id:
 *		screen_name:
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/lists/memberships
 */
	public function lists_memberships($params) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/memberships.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/lists/subscriptions
 *
 * @param string $user
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/lists/subscriptions
 * @deprecated
 */
	public function get_lists_subscriptions($user, $params = array()) {
		if (empty($user)) {
			return false;
		}

		if (!is_numeric($user)) {
			$params['screen_name'] = $user;
		} else {
			$params['user_id'] = $user;
		}

		// request
		return $this->lists_subscriptions($params);
	}

/**
 * GET lists/subscriptions
 *
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/subscriptions
 */
	public function lists_subscriptions($params) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/subscriptions.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == List Members Methods
	// ====================================================

/**
 * GET :user/:list_id/members
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      id:   The id or slug of the list.
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/:list_id/members
 * @deprecated
 */
	public function get_list_members($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		// request
		return $this->lists_members($params);
	}

/**
 * GET lists/members
 *
 * @param array  $params
 *		list_id:
 *		slig:
 *		owner_screen_name:
 *		owner_id:
 *      cursor:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/members
 */
	public function lists_members($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/members.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST :user/:list_id/members
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      id: The id or slug of the list.
 *      user_id: Specfies the ID of the user for whom to return results for. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/:user/:list_id/members
 * @deprecated
 */
	public function post_list_members($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}
		if (!is_numeric($params['user_id'])) {
			$params['screen_name'] = $user;
		} else {
			$params['user_id'] = $user;
		}

		// request
		return $this->lists_members_create($params);
	}

/**
 * POST lists/members/create
 *
 * @param array  $params
 *      list_id:
 *      slug:
 *      user_id:
 *      screen_name:
 *      owner_screen_name:
 *      owner_id:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/members/create
 * @deprecated
 */
	public function lists_members_create($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (empty($params['user_id']) && empty($params['screen_name']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/members/create.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST :user/:list_id/create_all
 *
 * Adds multiple members to a list, by specifying a comma-separated list of
 * member ids or screen names. The authenticated user must own the list to
 * be able to add members to it. Lists are limited to having 500 members,
 * and you are limited to adding up to 100 members to a list at a time with
 * this method.
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      user_id: A comma separated list of user IDs, up to 100 are allowed in a single request.
 *      screen_name: A comma separated list of screen names, up to 100 are allowed in a single request.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/:user/:list_id/create_all
 * @deprecated
 *
 * NOTE: http://groups.google.com/group/twitter-development-talk/browse_thread/thread/3e6ae4417160df39?pli=1
 *       now POST URL is http://api.twitter.com/1/:user/:list_id/members/create_all.json
 */
	public function post_list_members_create_all($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		// if $params = ('username1', 'username2', 'username3')
		if (!empty($params) && Set::numeric(array_keys($params))) {
			if (is_numeric($params[0])) {
				$params = array('user_id' => $params);
			} else {
				$params = array('screen_name' => $params);
			}
		}

		foreach (array('user_id', 'screen_name') as $key) {
			if (!empty($params[$key]) && is_array($params[$key])) {
				$params[$key] = join(',', $params[$key]);
			}
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		// request
		return $this->lists_members_create_all($params);
	}

/**
 * POST :user/:list_id/create_all
 *
 * Adds multiple members to a list, by specifying a comma-separated list of
 * member ids or screen names. The authenticated user must own the list to
 * be able to add members to it. Lists are limited to having 500 members,
 * and you are limited to adding up to 100 members to a list at a time with
 * this method.
 *
 * @param array  $params
 *		list_id:
 *		slug:
 *		owner_screen_name:
 *		owner_id:
 *      user_id: A comma separated list of user IDs, up to 100 are allowed in a single request.
 *      screen_name: A comma separated list of screen names, up to 100 are allowed in a single request.
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/members/create_all
 */
	public function lists_members_create_all($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (empty($params['user_id']) && empty($params['screen_name']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		foreach (array('user_id', 'screen_name') as $key) {
			if (!empty($params[$key]) && is_array($params[$key])) {
				$params[$key] = join(',', $params[$key]);
			}
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/members/create_all.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * DELETE :user/:id/members
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      id:      The id or slug of the list.
 *      user_id: Specfies the ID of the user for whom to return results for. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/delete/%3Auser/%3Alist_id/members
 * @deprecated
 */
	public function delete_list_members($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}
		if (!is_numeric($params['user_id'])) {
			$params['screen_name'] = $params['user_id'];
		} else {
			$params['user_id'] = $params['user_id'];
		}

		return $this->lists_members_destory($params);
	}

/**
 * POST lists/members/destroy
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      list_id:     The numerical id of the list.
 *      slug:        You can identify a list by its slug instead of its numerical id.
 *                   If you decide to do so, note that you'll also have to specify
 *                   the list owner using the owner_id or owner_screen_name parameters.
 *      user_id:     The ID of the user to remove from the list. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name: The screen name of the user for whom to remove from the list. Helpful for disambiguating when a valid screen name is also a user ID.
 *      owner_screen_name: The screen name of the user who owns the list being requested by a slug.
 *      owner_id:          The user ID of the user who owns the list being requested by a slug.
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/members/destroy
 */
	public function lists_members_destory($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (empty($params['user_id']) && empty($params['screen_name']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE . '1/lists/members/destroy.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/:list_id/members/:id
 *
 * @param string $user
 * @param string $list_id
 * @param string $id
 * @param array  $params
 *  *Optional*
 *      id:      The id or slug of the list.
 *      user_id: Specfies the ID of the user for whom to return results for. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/:list_id/members/:id
 * @deprecated
 */
	public function get_list_members_id($user, $list_id, $id, $params = array()) {
		if (empty($user) || empty($list_id) || empty($id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}
		if (!is_numeric($id)) {
			$params['screen_name'] = $id;
		} else {
			$params['user_id'] = $id;
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET lists/members/show
 *
 * @param array  $params
 *		list_id:
 *		slug:
 *		user_id:
 *		screen_name:
 *		owner_screen_name:
 *		owner_id:
 *		include_entities:
 *		skip_status:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/members/show
 */
	public function lists_members_show($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (empty($params['user_id']) && empty($params['screen_name']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/members/show.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == List Subscribers Methods
	// ====================================================

/**
 * GET :user/:list_id/subscribers
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/:list_id/subscribers
 * @deprecated
 */
	public function get_list_subscribers($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		return $this->lists_subscribers($params);
	}

/**
 * GET lists/subscribers
 *
 * @param array  $params
 *		list_id:
 *		slig:
 *		owner_screen_name:
 *		owner_id:
 *      cursor:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/subscribers
 */
	public function lists_subscribers($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/subscribers.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST :user/:list_id/subscribers
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/:user/:list_id/subscribers
 */
	public function post_list_subscribers($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		return $this->lists_subscribers_create($params);
	}

/**
 * POST lists/subscribers/create
 *
 * @param array  $params
 *      list_id:
 *      slug:
 *      owner_screen_name:
 *      owner_id:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/subscribers/create
 * @deprecated
 */
	public function lists_subscribers_create($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/subscribers/create.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * DELETE /:user/:list_id/subscribers
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/delete/:user/:id/subscribers
 * @deprecated
 */
	public function delete_list_subscribers($user, $list_id, $params = array()) {
		if (empty($user) || empty($list_id) || empty($params)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}

		return $this->lists_subscribers_destory($params);
	}

/**
 * POST lists/subscribers/destroy
 *
 * @param string $user
 * @param string $list_id
 * @param array  $params
 *      list_id:
 *      slug
 *      owner_screen_name:
 *      owner_id:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/post/lists/subscribers/destroy
 */
	public function lists_subscribers_destory($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE . '1/lists/subscribers/destroy.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET :user/:list_id/subscribers/:id
 *
 * @param string $user
 * @param string $list_id
 * @param string $id
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/:user/:list_id/subscribers/:id
 * @deprecated
 */
	public function get_list_subscribers_id($user, $list_id, $id, $params = array()) {
		if (empty($user) || empty($list_id) || empty($id)) {
			return false;
		}

		if (!is_numeric($list_id)) {
			$params['slug'] = $list_id;
			if (is_numeric($user)) {
				$params['owner_id'] = $user;
			} else {
				$params['owner_screen_name'] = $user;
			}
		} else {
			$params['list_id'] = $list_id;
		}
		if (!is_numeric($id)) {
			$params['screen_name'] = $id;
		} else {
			$params['user_id'] = $id;
		}

		return $this->lists_subscribers_show($params);
	}

/**
 * GET lists/subscribers/show
 *
 * @param array  $params
 *		list_id:
 *		slug:
 *		user_id:
 *		screen_name:
 *		owner_screen_name:
 *		owner_id:
 *		include_entities:
 *		skip_status:
 *
 * @return array|false
 * @see https://dev.twitter.com/docs/api/1/get/lists/subscribers/show
 */
	public function lists_subscribers_show($params) {
		if ((empty($params['list_id']) && empty($params['slug']))
			|| (empty($params['user_id']) && empty($params['screen_name']))
			|| (isset($params['slug']) && empty($params['owner_screen_name']) && empty($params['owner_id']))
		) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/lists/subscribers/show.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Direct Message Methods
	// ====================================================

/**
 * GET direct_messages
 *
 * @param array  $params
 *  *Optional*
 *     since_id:  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:    Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:     Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:      Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/direct_messages
 */
	public function direct_messages($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/direct_messages.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET direct_messages/sent
 *
 * @param array  $params
 *  *Optional*
 *     since_id:  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
 *     max_id:    Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
 *     count:     Specifies the number of statuses to retrieve. May not be greater than 200.
 *     page:      Specifies the page of results to retrieve. Note: there are pagination limits.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/direct_messages/sent
 */
	public function direct_messages_sent($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/direct_messages/sent.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST direct_messages/new
 *
 * @param array  $params
 *  *Required*
 *     user: The ID or screen name of the recipient user.
 *     text: The text of your direct message.  Be sure to URL encode as necessary, and keep it under 140 characters.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/direct_messages/new
 */
	public function direct_messages_new($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/direct_messages/new.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST direct_messages/destroy
 *
 * @param string $id
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/direct_messages/destroy
 */
	public function direct_messages_destroy($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/direct_messages/destroy/%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Friendship Methods
	// ====================================================

/**
 * POST friendships/create/:id
 *
 * @param array  $params
 *  *Required*
 *      id:          The ID or screen name of the user to befriend.
 *      user_id:     Specfies the ID of the user to befriend. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name: Specfies the screen name of the user to befriend. Helpful for disambiguating when a valid screen name is also a user ID.
 *  *Optional*
 *      follow:      Enable notifications for the target user.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/friendships/create/:id
 */
	public function friendships_create($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/friendships/create%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST friendships/destroy
 *
 * @param array  $params
 *  *Required*
 *      id:          The ID or screen name of the user to befriend.
 *      user_id:     Specfies the ID of the user to befriend. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name: Specfies the screen name of the user to befriend. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/friendships/destroy
 */
	public function friendships_destroy($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/friendships/destroy%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET friendships/exists
 *
 * @param string $id
 * @param array  $params
 *  *Required*
 *      user_a:  The ID or screen_name of the subject user.
 *      user_b:  The ID or screen_name of the user to test for following.
 *
 * @return true|false
 * @see http://dev.twitter.com/doc/get/friendships/exists
 */
	public function friendships_exists($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/friendships/exists.json';
		$method = 'GET';

		// request
		$check = $this->_request($this->_buildRequest($url, $method, $params));
		return !is_array($check) && $check;
	}

/**
 * GET friendships/show
 *
 * @param string $id
 * @param array  $params
 *  *Optional*
 *      source_id: The user_id of the subject user.
 *      source_screen_name: The screen_name of the subject user.
 *      target_id: The user_id of the target user.
 *      target_screen_name: The screen_name of the target user.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/friendships/show
 */
	public function friendships_show($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/friendships/show.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET friendships/incoming
 *
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/friendships/incoming
 */
	public function friendships_incoming($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/friendships/incoming.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET friendships/outgoing
 *
 * @param array  $params
 *  *Optional*
 *      cursor:
 *
 * @return true|false
 * @see http://dev.twitter.com/doc/get/friendships/outgoing
 */
	public function friendships_outgoing($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/friendships/outgoing.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Social Graph Methods
	// ====================================================

/**
 * GET friends/ids
 *
 * @param array  $params
 *  *Optional*
 *      id:           The ID or screen_name of the user to retrieve the friends ID list for.
 *      user_id:      Specfies the ID of the user for whom to return the friends list. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the user for whom to return the friends list. Helpful for disambiguating when a valid screen name is also a user ID.
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/friends/ids
 */
	public function friends_ids($params = array()) {
		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/friends/ids%s.json', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET followers/ids
 *
 * @param array  $params
 *      id:           The ID or screen_name of the user to retrieve the friends ID list for.
 *      user_id:      Specfies the ID of the user for whom to return the friends list. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the user for whom to return the friends list. Helpful for disambiguating when a valid screen name is also a user ID.
 *      cursor:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/followers/ids
 */
	public function followers_ids($params = array()) {
		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/followers/ids%s.json', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Account Methods
	// ====================================================

/**
 * GET account/verify_credentials
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/account/verify_credentials
 */
	public function account_verify_credentials($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/verify_credentials.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET account/rate_limit_status
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/account/rate_limit_status
 */
	public function account_rate_limit_status($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/rate_limit_status.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST account/end_session
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/account/end_session
 */
	public function account_end_session($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/end_session.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST account/update_delivery_device
 *
 * @param array  $params
 *  *Required*
 *      device: Delivery device type to send updates to.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/account/update_delivery_device
 */
	public function account_update_delivery_device($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/update_delivery_device.json';
		$method = 'POST';

		if (is_scalar($params)) {
			$params = array('device' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST account/update_profile_colors
 *
 * @param array  $params
 *  *Optional*
 *      profile_background_color:
 *      profile_text_color:
 *      profile_link_color:
 *      profile_sidebar_fill_color:
 *      profile_sidebar_border_color:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/account/update_profile_colors
 */
	public function account_update_profile_colors($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/update_profile_colors.json';
		$method = 'POST';

		if (is_scalar($params)) {
			$params = array('device' => $params);
		}

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST account/update_profile_image
 *
 * @param array  $params
 *  *Required*
 *      image: The avatar image for the profile. Must be a valid GIF, JPG, or PNG image of less than 700 kilobytes in size.
 *             Images with width larger than 500 pixels will be scaled down.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/account/update_profile_image
 *
 * TODO: check run
 */
	public function account_update_profile_image($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/update_profile_image.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST account/update_profile_background_image
 *
 * @param array  $params
 *  *Required*
 *      image: The background image for the profile. Must be a valid GIF, JPG, or PNG image of less than 800 kilobytes in size.
 *             Images with width larger than 2048 pixels will be forceably scaled down.
 *  *Optional*
 *      tile:  Whether or not to tile the background image. If set to true the background image will be displayed tiled.
 *             The image will not be tiled otherwise.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/account/update_profile_background_image
 *
 * TODO: check run
 */
	public function account_update_profile_background_image($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/update_profile_background_image.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST account/update_profile
 *
 * @param array  $params
 *  *Optional*
 *      name: Maximum of 20 characters.
 *      url:  Maximum of 100 characters. Will be prepended with "http://" if not present.
 *      location:    Maximum of 30 characters. The contents are not normalized or geocoded in any way.
 *      description: Maximum of 160 characters.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/account/update_profile
 */
	public function account_update_profile($params = array()) {
		if (empty($params)) {
			return false;
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/account/update_profile.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Favorite Methods
	// ====================================================

/**
 * GET favorites
 *
 * @param array  $params
 *  *Optional*
 *     id:    The ID or screen name of the user for whom to request a list of favorite statuses.
 *     page:  Specifies the page of favorites to retrieve.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/favorites
 */
	public function favorites($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/favorites.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST favorites/:id/create
 *
 * @param string $id *Required*  The ID of the status to favorite.
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/favorites/:id/create
 */
	public function favorites_create($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/favorites/create/%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST favorites/destroy
 *
 * @param string $id *Required*  The ID of the status to un-favorite.
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/favorites/destroy
 */
	public function favorites_destroy($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/favorites/destroy/%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Notification Methods
	// ====================================================

/**
 * POST notifications/follow
 *
 * @param array  $params
 *  *Required*
 *      id:           The ID or screen name of the user to follow with device updates.
 *  *Optional*
 *      user_id:      Specfies the ID of the user to follow with device updates. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the user to follow with device updates. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/notifications/follow
 */
	public function notifications_follow($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {

			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/notifications/follow%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST notifications/leave
 *
 * @param array  $params
 *  *Required*
 *      id:           The ID or screen name of the user to follow with device updates.
 *  *Optional*
 *      user_id:      Specfies the ID of the user to follow with device updates. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the user to follow with device updates. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/notifications/leave
 */
	public function notifications_leave($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {

			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/notifications/leave%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Block Methods
	// ====================================================

/**
 * POST blocks/create
 *
 * @param array  $params
 *  *Optional*
 *      id:           The ID or screen_name of the potentially blocked user.
 *      user_id:      Specfies the ID of the potentially blocked user. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the potentially blocked user. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/blocks/create
 */
	public function blocks_create($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/blocks/create%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST blocks/destroy
 *
 * @param array  $params
 *  *Optional*
 *      id:           The ID or screen_name of the potentially blocked user.
 *      user_id:      Specfies the ID of the potentially blocked user. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the potentially blocked user. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/blocks/destroy
 */
	public function blocks_destroy($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/blocks/destroy%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET blocks/exists
 *
 * @param array  $params
 *      id:           The ID or screen_name of the potentially blocked user.
 *      user_id:      Specfies the ID of the potentially blocked user. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the potentially blocked user. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/blocks/exists
 *
 * TODO: Block does not exist, returned as an HTTP 404
 */
	public function blocks_exists($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$id = '';
		if (!empty($params['id'])) {
			$id = '/' . $params['id'];
			unset($params['id']);
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/blocks/exists%s.json', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET blocks/blocking
 *
 * @param array  $params
 *      page: Specifies the page number of the results beginning at 1. A single page contains 20 ids.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/blocks/blocking
 */
	public function blocks_blocking($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/blocks/blocking.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET blocks/blocking/ids
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/blocks/blocking/ids
 */
	public function blocks_blocking_ids($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/blocks/blocking/ids.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Spam Reporting Methods
	// ====================================================

/**
 * POST report_spam
 *
 * @param array  $params
 *  *Optional*
 *      id:           The ID or screen_name of the potentially blocked user.
 *      user_id:      Specfies the ID of the potentially blocked user. Helpful for disambiguating when a valid user ID is also a valid screen name.
 *      screen_name:  Specfies the screen name of the potentially blocked user. Helpful for disambiguating when a valid screen name is also a user ID.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/report_spam
 */
	public function report_spam($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('id' => $params);
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/report_spam.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Saved Searches Methods
	// ====================================================

/**
 * GET saved_searches
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/saved_searches
 */
	public function saved_searches($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/saved_searches.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET saved_searches/show
 *
 * @param string $id
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/saved_searches/show
 */
	public function saved_searches_show($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/saved_searches/show/%s.json', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST saved_searches/create
 *
 * @param array  $params
 *  *Required*
 *      query:   The query of the search the user would like to save.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/post/saved_searches/create
 */
	public function saved_searches_create($params = array()) {
		if (empty($params)) {
			return false;
		}

		if (is_scalar($params)) {
			$params = array('query' => $params);
		}

		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/saved_searches/create.json';
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * POST saved_searches/destroy
 *
 * @param string $id *Required*  The id of the saved search to be deleted.
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/post/saved_searches/destroy
 */
	public function saved_searches_destroy($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/saved_searches/destroy/%s.json', $id);
		$method = 'POST';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == OAuth Methods
	// ====================================================

/**
 * GET/POST oauth/request_token
 *
 * @param  string $oauth_callback
 * @return array
 * @see http://dev.twitter.com/doc/post/oauth/request_token
 */
	public function oauth_request_token($oauth_callback = null) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . 'oauth/request_token';
		$method = 'GET';

		// get Request param
		$params = $this->_buildRequest($url, $method);

		if (empty($oauth_callback)) {
			$oauth_callback = $this->oauth_callback;
		}

		if (!preg_match('!^https?://!', $oauth_callback)) {
			$oauth_callback = Router::url($oauth_callback, true);
		}

		// add oauth callback
		$params['auth']['oauth_callback'] = $oauth_callback;

		// request
		$response = $this->_request($params, false);
		if ($this->_isXml($response)) {
			return $this->_getOAuthError($response);
		}

		parse_str($response, $response);
		if (!empty($response['oauth_token'])) {
			$this->oauth_token = $response['oauth_token'];
		}

		return $response;
	}

/**
 * Get Authorize URL
 *
 * @param  string $oauth_token
 * @return string
 * @see    http://dev.twitter.com/doc/get/oauth/authorize
 */
	public function oauth_authorize($oauth_token = '') {
		$url = self::TWITTER_API_URL_BASE_HTTPS . 'oauth/authorize';

		if (empty($oauth_token)) {
			$oauth_token = $this->oauth_token;
		}

		return $url . '?oauth_token=' . $oauth_token;
	}

/**
 * Get Authenticate URL
 *
 * @param  string $oauth_token
 * @return string
 * @see    http://dev.twitter.com/doc/get/oauth/authenticate
 */
	public function oauth_authenticate($oauth_token = '') {
		$url = self::TWITTER_API_URL_BASE_HTTPS . 'oauth/authenticate';

		if (empty($oauth_token)) {
			$oauth_token = $this->oauth_token;
		}

		return $url . '?oauth_token=' . $oauth_token;
	}

/**
 * GET/POST oauth/access_token
 *
 * @param  string $oauth_token
 * @param  string $oauth_verifier
 * @return array
 * @see    http://dev.twitter.com/doc/post/oauth/access_token
 */
	public function oauth_access_token($oauth_token, $oauth_verifier) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . 'oauth/access_token';
		$method = 'POST';

		// get Request param
		$params = $this->_buildRequest($url, $method);

		// add oauth param
		$params['auth']['oauth_token'] = $oauth_token;
		$params['auth']['oauth_verifier'] = $oauth_verifier;

		// request
		$response = $this->_request($params, false);

		if ($this->_isXml($response)) {
			return $this->_getOAuthError($response);
		}

		parse_str($response, $response);
		if (!empty($response['oauth_token'])) {
			$this->oauth_token = $response['oauth_token'];
		}

		if (!empty($response['oauth_token_secret'])) {
			$this->oauth_token_secret = $response['oauth_token_secret'];
		}

		return $response;
	}

	// ====================================================
	// == Local Trends Methods
	// ====================================================

/**
 * GET trends/available
 *
 * @param array  $params
 *      lat_for_trends:
 *      long_for_trends:
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/trends/available
 * @see http://developer.yahoo.com/geo/geoplanet/
 */
	public function trends_available($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/trends/available.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET trends/location/:woeid
 *
 * @param string $woeid The WOEID of the location to be querying for.
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/trends/location/:woeid
 * @see http://developer.yahoo.com/geo/geoplanet/
 */
	public function trends_location($woeid, $params = array()) {
		if (empty($woeid)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/trends/%s.json', $woeid);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Geo methods
	// ====================================================

/**
 * geo/nearby_places
 *
 * @param array  $params
 *      lat.  Optional but required if long provided or if ip is not provided.  The latitude to query about.  Valid ranges are -90.0 to +90.0 (North is positive) inclusive.
 *      long. Optional but required if lat provided or if ip is not provided. The longitude to query about.  Valid ranges are -180.0 to +180.0 (East is positive) inclusive.
 *      ip.       Optional but required if lat and long are not provided.  The IP address that the call is coming from. Twitter will geo-IP the address.
 *      accuracy. A hint on the "region" in which to search.
 *                          If a number, then this is a radius in meters, but it can also take a string that is suffixed with ft to specify feet.
 *                          If this is not passed in, then it is assumed to be 0m.
 *                          If coming from a device, in practice, this value is whatever accuracy the device has measuring its location
 *                          (whether it be coming from a GPS, WiFi triangulation, etc.).
 *      granularity.  The minimal granularity of data to return.  If this is not passed in, then neighborhood is assumed.  city can also be passed.
 *      max_results.  A hint as to the number of results to return.
 *                             This does not guarantee that the number of results returned will equal max_results, but instead informs how many "nearby" results to return.
 *                             Ideally, only pass in the number of places you intend to display to the user here.
 *
 * @return array|false
 * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-geo-nearby_places
 */
	public function geo_nearby_places($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/geo/nearby_places.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET geo/reverse_geocode
 *
 * @param array  $params
 *  *Required*
 *      lat:    The latitude to query about.  Valid ranges are -90.0 to +90.0 (North is positive) inclusive.
 *      long:   The longitude to query about.  Valid ranges are -180.0 to +180.0 (East is positive) inclusive.
 *  *Optional*
 *      accuracy: A hint on the "region" in which to search.
 *                If a number, then this is a radius in meters, but it can also take a string that is suffixed with ft to specify feet.
 *                If this is not passed in, then it is assumed to be 0m.
 *                If coming from a device, in practice, this value is whatever accuracy the device has measuring its location
 *                (whether it be coming from a GPS, WiFi triangulation, etc.).
 *      granularity:  The minimal granularity of data to return.  If this is not passed in, then neighborhood is assumed.  city can also be passed.
 *      max_results:  A hint as to the number of results to return.
 *                    This does not guarantee that the number of results returned will equal max_results, but instead informs how many "nearby" results to return.
 *                    Ideally, only pass in the number of places you intend to display to the user here.
 *
 * @return array|false
 * @see http://dev.twitter.com/doc/get/geo/reverse_geocode
 */
	public function geo_reverse_geocode($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/geo/reverse_geocode.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

/**
 * GET geo/id/:place_id
 *
 * @param string $id *Required*  The ID of the location to query about.
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/geo/id/:place_id
 */
	public function geo_id($id, $params = array()) {
		if (empty($id)) {
			return false;
		}

		$url = sprintf(self::TWITTER_API_URL_BASE_HTTPS . '1/geo/id/%s.json', $id);
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == Help Methods
	// ====================================================

/**
 * GET help/test
 *
 * @param array  $params
 * @return array|false
 * @see http://dev.twitter.com/doc/get/help/test
 */
	public function help_test($params = array()) {
		$url = self::TWITTER_API_URL_BASE_HTTPS . '1/help/test.json';
		$method = 'GET';

		// request
		return $this->_request($this->_buildRequest($url, $method, $params));
	}

	// ====================================================
	// == @Anywhere Methods
	// ====================================================

/**
 * return @Anywhere identity cookie
 *
 * @param  string $id
 * @return string|null
 * @see   http://dev.twitter.com/anywhere/begin#current-user
 */
	public function getAnywhereIdentity($id = null) {
		if (empty($id)) {
			return null;
		}

		return $id . ':' . sha1($id . $this->oauth_consumer_secret);
	}

}
