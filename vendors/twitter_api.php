<?php
App::import('Core', 'ConnectionManager');
App::import('Datasouce', 'TwitterKit.TwitterSource');
/**
 * TwitterKit TwitterApi
 *
 * defined Twitter Behavior/Component's common methods
 *
 * for CakePHP 1.3.0
 * PHP version 5
 *
 * Copyright 2010, elasticconsultants.com
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @deprecated
 * @version    1.0
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010 elasticconsultants co.,ltd.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.vendors
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterApi extends Object {

    /**
     *
     * @var TwitterSource
     */
    public $DataSource;

    /**
     *
     * @var array
     */
    public $default = array(
        'datasource' => 'twitter',
        'fields' => array(
            'oauth_token' => 'oauth_token',
            'oauth_token_secret' => 'oauth_token_secret'),
    );

    /**
     *
     * @var array
     */
    public $settings = array();

    /**
     *
     */
    public function __construct() {

        $this->setup();

    }

    /**
     * setup datasource
     *
     * @param string $key    eg. $model->alias
     * @param array  $config
     */
    public function setup($key = 'default', $config = array()) {

        $this->settings[$key] = Set::merge($this->default, $config);
        $this->getTwitterSource($key);

    }

    /**
     * get DataSource Object
     *
     * @param string $key    eg. $model->alias
     * @return TwitterSource
     */
    public function getTwitterSource($key = 'default') {

        $ds = ConnectionManager::getDataSource($this->settings[$key]['datasource']);

        if (get_class($ds) == 'TwitterSource' || is_subclass_of($ds, 'TwitterSource')) {

            $this->DataSource = $ds;

        }

        return $this->DataSource;

    }

    /**
     * set DataSource Object
     *
     * @param string $key
     * @param string $datasource
     */
    public function setTwitterSource($key = 'default', $datasource = null) {

        if (empty($datasource)
        || (!in_array($datasource, array_keys(get_class_vars('DATABASE_CONFIG'))) && !in_array($datasource, ConnectionManager::sourceList()))) {

            return;

        }

        $this->settings[$key]['datasource'] = $datasource;

        $this->getTwitterSource($key);

    }

    /**
     * make OAuth Authorize URL
     *
     * @param string $key
     * @param string $callback_url
     * @return string authorize_url
     */
    public function getAuthorizeUrl($key = 'default', $callback_url = null) {

        $token = $this->DataSource->oauth_request_token($callback_url);

        return $this->DataSource->oauth_authorize();
    }

    /**
     * make OAuth Authenticate URL
     *
     * @param string $key
     * @param string $callback_url
     * @return string authorize_url
     */
    public function getAuthenticateUrl($key = 'default', $callback_url = null) {

        $token = $this->DataSource->oauth_request_token($callback_url);

        return $this->DataSource->oauth_authenticate();
    }

    /**
     * get OAuth Access Token
     *
     * @param string $key
     * @param string $oauth_token
     * @param string $oauth_verifier
     * @return array|false
     */
    public function getAccessToken($key = 'default', $oauth_token = null, $oauth_verifier = null) {

        if (empty($oauth_token) || empty($oauth_verifier)) {

            return false;

        }

        $token = $this->DataSource->oauth_access_token($oauth_token, $oauth_verifier);

        return $token;

    }

}