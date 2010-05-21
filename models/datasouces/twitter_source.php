<?php
App::import('Core', 'Xml');
App::import('vendor', 'TwitterKit.HttpSocketOauth', array('file' => 'http_socket_oauth' . DS .'http_socket_oauth.php'));
/**
 * Twitter API Datasouce
 *
 * for CakePHP 1.3.0
 * PHP version 5.2 upper
 *
 * Copyright 2010, elasticconsultants.com
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.0
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010 elasticconsultants co.,ltd.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.models.datasouces
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
class TwitterSource extends DataSource {

    public $description = 'Twitter API';

    /**
     *
     * @var HttpSocketOauth
     */
    public $Http;

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
    );

    /**
     *
     * @param array $config
     */
    public function __construct($config) {

        parent::__construct($config);
         
        $this->Http =& new HttpSocketOauth();
         
        $this->reset();
    }

    /**
     * Reset object vars
     */
    public function reset() {

        $this->oauth_consumer_key    = $this->config['oauth_consumer_key'];
        $this->oauth_consumer_secret = $this->config['oauth_consumer_secret'];
        $this->oauth_token           = $this->config['oauth_token'];
        $this->oauth_token_secret    = $this->config['oauth_token_secret'];
        $this->oauth_callback        = $this->config['oauth_callback'];

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

            $this->oauth_token        = $token['oauth_token'];
            $this->oauth_token_secret = $token['oauth_token_secret'];

            return true;

        } else if (!empty($token) && !empty($secret)) {

            $this->oauth_token        = $token;
            $this->oauth_token_secret = $secret;

            return true;
        }

        return false;
    }

    /**
     * Request API and process responce
     *
     * @param array $params
     * @param bool  $is_process
     * @return mixed
     */
    protected function _request($params, $is_process = true) {

        $response = $this->Http->request($params);

        if ($is_process) {

            $response = json_decode($response, true);

        }

        return $response;
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

            $url = self::TWITTER_API_URL_BASE . $url;

        }

        $uri = parse_url($url);

        // add GET params
        if (!empty($body) && $method == 'GET') {

            $uri['query'] = array_merge($uri['query'], $body);
            $body = array();

        }

        $params = compact('uri', 'method', 'body');

        // -- Set Auth parameter
        if (!empty($this->oauth_consumer_key) && !empty($this->oauth_consumer_secret)) {

            // OAuth
            $params['auth']['method'] = 'OAuth';
            $params['auth']['oauth_consumer_key']    = $this->oauth_consumer_key;
            $params['auth']['oauth_consumer_secret'] = $this->oauth_consumer_secret;

            if (!empty($this->oauth_token) && !empty($this->oauth_token_secret)) {

                $params['auth']['oauth_token']        = $this->oauth_token;
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
    // == Search API Methods
    // ====================================================

    // ====================================================
    // == Timeline Methods
    // ====================================================


    // ====================================================
    // == Status Methods
    // ====================================================


    // ====================================================
    // == List Methods
    // ====================================================

    /**
     * POST lists (create)
     *
     * @param string $user
     * @param array  $params
     *                  name.         Required. The name of the list you are creating.
     *                  mode.         Optional. Whether your list is public or private. Values can be public or private. Lists are public by default if no mode is specified.
     *                  description.  Optional. The description of the list you are creating.
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-lists
     */
    public function post_lists($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists.json', $user);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * POST lists id (update)
     *
     * @param string $user
     * @param string $id
     * @param array  $params
     *                  name.         Required. The name of the list you are creating.
     *                  mode.         Optional. Whether your list is public or private. Values can be public or private. Lists are public by default if no mode is specified.
     *                  description.  Optional. The description of the list you are creating.
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-lists-id
     */
    public function post_lists_id($user, $id, $params = array()) {

        if (empty($user) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s.json', $user, $id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET lists (index)
     *
     * @param string $user
     * @param array  $params
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-lists
     */
    public function get_lists($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists.json', $user);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET lists id (show)
     *
     * @param string $user
     * @param string $id
     * @param array  $params
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-id
     */
    public function get_lists_id($user, $id, $params = array()) {

        if (empty($user) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s.json', $user, $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * DELETE list id (destroy)
     *
     * @param string $user
     * @param string $id
     * @param array  $params
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-DELETE-list-members
     */
    public function delete_lists_id($user, $id, $params = array()) {

        if (empty($user) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s.json', $user, $id);
        $method = 'POST';

        $params['_method'] = 'DELETE';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list statuses
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     *                  since_id.  Optional.  Returns only statuses with an ID greater than (that is, more recent than) the specified ID.
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/team/statuses.xml?since_id=12345
     *                  max_id. Optional.  Returns only statuses with an ID less than (that is, older than) or equal to the specified ID.
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/team/statuses.xml?max_id=54321
     *                  per_page.  Optional.  Specifies the number of statuses to retrieve. May not be greater than 200.
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/team/statuses.xml?per_page=5
     *                  page. Optional. Specifies the page of results to retrieve. Note: there are pagination limits.
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/team/statuses.xml?page=3
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-statuses
     */
    public function get_lists_statuses($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/%s/statuses.json', $user, $list_id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list memberships
     *
     * @param string $user
     * @param array  $params
     *                  cursor. Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                          Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/memberships.xml?cursor=-1
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/memberships.xml?cursor=-1300794057949944903
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-statuses
     */
    public function get_lists_memberships($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/memberships.json', $user);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list subscriptions
     *
     * @param string $user
     * @param array  $params
     *                  cursor. Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                          Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/subscriptions.xml?cursor=-1
     *                      o Example: http://api.twitter.com/1/twitterapi/lists/subscriptions.xml?cursor=-1300794057949944903
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-statuses
     */
    public function get_lists_subscriptions($user, $params = array()) {

        if (empty($user)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/lists/subscriptions.json', $user);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == List Members Methods
    // ====================================================

    /**
     * GET list members
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params
     *                  list_id.  Required. The id or slug of the list.
     *                  cursor.   Optional. Breaks the results into pages. A single page contains 20 lists. Provide a value of -1 to begin paging.
     *                            Provide values as returned to in the response body's next_cursor and previous_cursor attributes to page back and forth in the list.
     *                      o Example: http://api.twitter.com/1/twitterapi/team/members.xml?cursor=-1
     *                      o Example: http://api.twitter.com/1/twitterapi/team/members.xml?cursor=-1300794057949944903
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-members
     */
    public function get_list_members($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members.json', $user, $list_id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * POST list members
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params array('id' => user_id)
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-POST-list-members
     */
    public function post_list_members($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members.json', $user, $list_id);
        $method = 'POST';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * DELETE list members
     *
     * @param string $user
     * @param string $list_id
     * @param array  $params array('id' => user_id)
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-DELETE-list-members
     */
    public function delete_list_members($user, $list_id, $params = array()) {

        if (empty($user) || empty($list_id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members.json', $user, $list_id);
        $method = 'POST';

        $params['_method'] = 'DELETE';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }

    /**
     * GET list members id
     *
     * @param string $user
     * @param string $list_id
     * @param string $id
     * @param array  $params
     * @return object|false
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-GET-list-members-id
     */
    public function get_list_members_id($user, $list_id, $id, $params = array()) {

        if (empty($user) || empty($list_id) || empty($id)) {
            return false;
        }

        $url    = sprintf('http://api.twitter.com/1/%s/%s/members/%s.json', $user, $list_id, $id);
        $method = 'GET';

        // request
        return $this->_request($this->_buildRequest($url, $method, $params));
    }


    // ====================================================
    // == List Subscribers Methods
    // ====================================================


    // ====================================================
    // == Direct Message Methods
    // ====================================================


    // ====================================================
    // == Friendship Methods
    // ====================================================


    // ====================================================
    // == Social Graph Methods
    // ====================================================


    // ====================================================
    // == Account Methods
    // ====================================================


    // ====================================================
    // == Favorite Methods
    // ====================================================


    // ====================================================
    // == Notification Methods
    // ====================================================

    // ====================================================
    // == Block Methods
    // ====================================================

    // ====================================================
    // == Spam Reporting Methods
    // ====================================================

    // ====================================================
    // == Saved Searches Methods
    // ====================================================

    // ====================================================
    // == OAuth Methods
    // ====================================================

    /**
     * Get OAuth Request Token
     *
     * @param  string $oauth_callback
     * @return array
     * @see http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-request_token
     */
    public function oauth_request_token($oauth_callback = null) {

        $url    = 'http://api.twitter.com/oauth/request_token';
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
     * @see    http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-authorize
     */
    public function oauth_authorize($oauth_token = '') {

        $url    = 'http://api.twitter.com/oauth/authorize';

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
     * @see    http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-authenticate
     */
    public function oauth_authenticate($oauth_token = '') {

        $url    = 'http://api.twitter.com/oauth/authenticate';

        if (empty($oauth_token)) {
            $oauth_token = $this->oauth_token;
        }

        return $url . '?oauth_token=' . $oauth_token;
    }

    /**
     * get oauth access token
     *
     * @param  string $oauth_token
     * @param  string $oauth_verifier
     * @return array
     * @see    http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-oauth-access_token
     */
    public function oauth_access_token($oauth_token, $oauth_verifier) {

        $url    = 'http://api.twitter.com/oauth/access_token';
        $method = 'GET';

        // get Request param
        $params = $this->_buildRequest($url, $method);

        // add oauth param
        $params['auth']['oauth_token']    = $oauth_token;
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

    // ====================================================
    // == Geo methods
    // ====================================================

    // ====================================================
    // == Help Methods
    // ====================================================



}