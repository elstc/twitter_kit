<?php
App::uses('ConnectionManager', 'Model');
App::uses('DataSource', 'Model/DataSource');
/**
 * TwitterKit Twitter Component
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
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010, ELASTIC Consultants Inc.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.controllers.components
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterComponent extends Component {

    public $name = 'Twitter';

    public $components = array('Cookie' => array());

    public static $defaultSettings = array(
        'datasource' => 'twitter',
        'fields' => array(
            'oauth_token' => 'oauth_token',
            'oauth_token_secret' => 'oauth_token_secret'),
    );

    /**
     *
     * @var AppController
     */
    public $controller;

    /**
     *
     * @var TwitterSource
     */
    public $DataSource;

    /**
     *
     * @var CookieComponent
     */
    public $Cookie;

    /**
     * default: 5min
     *
     * @var int
     */
    CONST OAUTH_URL_COOKIE_EXPIRE = 300;

    /**
     *
     * @param ComponentCollection $collection
     * @param array $settings
     */
    public function __construct(ComponentCollection $collection, $settings = array()) {

        $settings = Set::merge(self::$defaultSettings, $settings);
        $this->components['Cookie'] += array('path' => Router::url('/'));
        parent::__construct($collection, $settings);

        // seems hack but for lazy loading and auto complete with IDE(or text editor)
        unset($this->Cookie, $this->DataSource);

        $this->controller = $collection->getController();

    }

    /**
     * loads DataSource or sub components lazily.
     *
     * @param string $name
     */
    public function __get($name) {
        if ($name === 'DataSource') {
            return $this->getTwitterSource();
        }

        return parent::__get($name);
    }

    /**
     * get DataSource Object
     *
     * @return TwitterSource
     */
    public function getTwitterSource() {

        $ds = ConnectionManager::getDataSource($this->settings['datasource']);

        if (get_class($ds) == 'TwitterSource' || is_subclass_of($ds, 'TwitterSource')) {

            $this->DataSource = $ds;

        }

        return $this->DataSource;

    }

    /**
     * set DataSource Object
     *
     * @param string $datasource
     */
    public function setTwitterSource($datasource) {

        if (empty($datasource)
        || (!in_array($datasource, array_keys(get_class_vars('DATABASE_CONFIG'))) && !in_array($datasource, ConnectionManager::sourceList()))) {

            return;

        }

        $this->settings['datasource'] = $datasource;

        $this->getTwitterSource();

    }

    /**
     *
     * @param AppController $controller
     */
    public function startup($controller) {

        $this->controller = $controller;

    }

    /**
     * make OAuth Authorize URL
     *
     * @param string $callback_url
     * @param bool   $use_cookie
     * @return string authorize_url
     */
    public function getAuthorizeUrl($callback_url = null, $use_cookie = false) {

        // -- check Cookie
        $cookie_key = $this->_getAuthorizeUrlCookieName();

        if ($use_cookie && $this->Cookie->read($cookie_key)) {

            return $this->Cookie->read($cookie_key);

        }

        // -- request token
        $token = $this->DataSource->oauth_request_token($callback_url);

        $url = $this->DataSource->oauth_authorize();

        // -- set cookie
        if ($use_cookie) {

            $this->Cookie->write($cookie_key, $url, true, self::OAUTH_URL_COOKIE_EXPIRE);

        }

        return $url;
    }

    /**
     * make OAuth Authenticate URL
     *
     * @param string $callback_url
     * @param bool   $use_cookie
     * @return string authorize_url
     */
    public function getAuthenticateUrl($callback_url = null, $use_cookie = false) {

        // -- check Cookie
        $cookie_key = $this->_getAuthenticateUrlCookieName();

        if ($use_cookie && $this->Cookie->read($cookie_key)) {

            return $this->Cookie->read($cookie_key);

        }

        // -- request token
        $token = $this->DataSource->oauth_request_token($callback_url);

        $url = $this->DataSource->oauth_authenticate();

        // -- set cookie
        if ($use_cookie) {

            $this->Cookie->write($cookie_key, $url, true, self::OAUTH_URL_COOKIE_EXPIRE);

        }

        return $url;
    }

    /**
     * get OAuth Access Token
     *
     * @return array|false
     */
    public function getAccessToken() {

        // remove authorize/authenticate url cookie
        $this->deleteAuthorizeCookie();

        if (empty($this->controller->request->query['oauth_token']) || empty($this->controller->request->query['oauth_verifier'])) {

            return false;

        }

        $oauth_token    = $this->controller->request->query['oauth_token'];
        $oauth_verifier = $this->controller->request->query['oauth_verifier'];

        $token = $this->DataSource->oauth_access_token($oauth_token, $oauth_verifier);

        return $token;

    }

    /**
     * set OAuth Access Token
     *
     * @param mixed $token
     * @param string $secret
     * @return true|false
     */
    public function setToken($token, $secret = null) {

        if (is_array($token) && !empty($token[$this->settings['fields']['oauth_token']]) && !empty($token[$this->settings['fields']['oauth_token_secret']])) {

            $secret = $token[$this->settings['fields']['oauth_token_secret']];
            $token  = $token[$this->settings['fields']['oauth_token']];

        }

        return $this->DataSource->setToken($token, $secret);

    }

    /**
     * set OAuth Access Token by Authorized User
     *
     * @param  array $user
     */
    public function setTokenByUser($user = null) {

        if (empty($user) && !empty($this->controller->Auth) && is_object($this->controller->Auth)) {

            $user = $this->controller->Auth->user();

        }

        return $this->setToken($user['User']);
    }

    /**
     * call TwitterSource methods
     *
     * @param string $name
     * @param array  $arg
     */
    public function __call($name, $arg) {

        if (in_array($name, get_class_methods('TwitterSource'))) {

            return call_user_func_array(array($this->DataSource, $name), $arg);

        }

    }

    /**
     * delete Authorize/Authenticate url cookie
     */
    public function deleteAuthorizeCookie() {

        $this->Cookie->delete($this->_getAuthorizeUrlCookieName());
        $this->Cookie->delete($this->_getAuthenticateUrlCookieName());

    }

    /**
     *
     * @return string
     */
    protected function _getAuthorizeUrlCookieName() {
        return $this->DataSource->configKeyName . '_authorize_url';
    }

    /**
     *
     * @return string
     */
    protected function _getAuthenticateUrlCookieName() {
        return $this->DataSource->configKeyName . '_authenticate_url';
    }
}