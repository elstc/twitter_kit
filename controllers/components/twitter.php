<?php
App::import('Core', 'ConnectionManager');
App::import('Datasouce', 'TwitterKit.TwitterSource');
/**
 * TwitterKit Twitter Component
 *
 * for CakePHP 1.3.0
 * PHP version 5
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
 * @subpackage twitter_kit.controllers.components
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterComponent extends Object {

    public $name = 'Twitter';

    public $components = array('Cookie');

    public $settings = array(
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
     * @param AppController $controller
     * @param array         $settings
     */
    public function initialize($controller, $settings = array()) {

        $this->settings = Set::merge($this->settings, $settings);

        $this->controller = $controller;

        $this->getTwitterSource();

        $this->Cookie->path = Router::url('/' . $this->controller->params['url']['url']);
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
    public function getAuthorizeUrl($callback_url = null, $use_cookie = true) {

        // -- check Cookie
        $cookie_key = $this->DataSource->configKeyName . '_authorize_url';

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
    public function getAuthenticateUrl($callback_url = null, $use_cookie = true) {

        // -- check Cookie
        $cookie_key = $this->DataSource->configKeyName . '_authenticate_url';

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

        if (empty($this->controller->params['url']['oauth_token']) || empty($this->controller->params['url']['oauth_verifier'])) {

            return false;

        }

        $oauth_token    = $this->controller->params['url']['oauth_token'];
        $oauth_verifier = $this->controller->params['url']['oauth_verifier'];

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

            call_user_func_array(array($this->DataSource, $name), $arg);

        }

    }

}