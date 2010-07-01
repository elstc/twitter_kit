<?php
App::import('Core', 'ConnectionManager');
App::import('Datasouce', 'TwitterKit.TwitterSource');
/**
 * TwitterKit Twitter Behavior
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
 * @subpackage twitter_kit.models.behaviors
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterBehavior extends ModelBehavior {

    /**
     *
     * @var TwitterSource
     */
    public $DataSource;

    public $default = array(
        'datasource' => 'twitter',
        'fields' => array(
            'oauth_token' => 'oauth_token',
            'oauth_token_secret' => 'oauth_token_secret'),
    );

    /**
     *
     * @param AppModel $model
     * @param array    $config
     */
    public function setup($model, $config = array()) {

        $this->settings[$model->alias] = Set::merge($this->default, $config);

        $this->getTwitterSource($model);
    }

    /**
     * get DataSource Object
     *
     * @param AppModel $model
     * @return TwitterSource
     */
    public function getTwitterSource($model) {

        $ds = ConnectionManager::getDataSource($this->settings[$model->alias]['datasource']);

        if (get_class($ds) == 'TwitterSource' || is_subclass_of($ds, 'TwitterSource')) {

            $this->DataSource = $ds;

        }

        return $this->DataSource;

    }

    /**
     * set DataSource Object
     *
     * @param AppModel $model
     * @param string $datasource
     */
    public function setTwitterSource($model, $datasource) {

        if (empty($datasource)
        || (!in_array($datasource, array_keys(get_class_vars('DATABASE_CONFIG'))) && !in_array($datasource, ConnectionManager::sourceList()))) {

            return;

        }

        $this->settings[$model->alias]['datasource'] = $datasource;

        $this->getTwitterSource($model);

    }

    /**
     * make OAuth Authorize URL
     *
     * @param AppModel $model
     * @param string $callback_url
     * @return string authorize_url
     */
    public function twitterAuthorizeUrl($model, $callback_url = null) {

        $token = $this->DataSource->oauth_request_token($callback_url);

        return $this->DataSource->oauth_authorize();
    }

    /**
     * make OAuth Authenticate URL
     *
     * @param AppModel $model
     * @param string $callback_url
     * @return string authorize_url
     */
    public function twitterAuthenticateUrl($model, $callback_url = null) {

        $token = $this->DataSource->oauth_request_token($callback_url);

        return $this->DataSource->oauth_authenticate();
    }

    /**
     * get OAuth Access Token
     *
     * @param AppModel $model
     * @param string   $oauth_token
     * @param string   $oauth_verifier
     * @return array|false
     */
    public function twitterAccessToken($model, $oauth_token = null, $oauth_verifier = null) {

        if (empty($oauth_token) || empty($oauth_verifier)) {

            return false;

        }

        $token = $this->DataSource->oauth_access_token($oauth_token, $oauth_verifier);

        return $token;

    }

    /**
     * set OAuth Access Token
     *
     * @param AppModel $model
     * @param mixed $token
     * @param string $secret
     * @return true|false
     */
    public function twitterSetToken($model, $token = null, $secret = null) {

        if (empty($token)) {

            // -- get from Model->data
            if (empty($model->data[$model->alias])) {

                return false;

            }

            $data = $model->data[$model->alias];

            if (empty($data[$this->settings[$model->alias]['fields']['oauth_token']])
            || empty($data[$this->settings[$model->alias]['fields']['oauth_token_secret']])) {
                 
                return false;
                 
            }

            $secret = $data[$this->settings[$model->alias]['fields']['oauth_token_secret']];
            $token  = $data[$this->settings[$model->alias]['fields']['oauth_token']];

        } else if (is_array($token)) {

            if (!empty($token[$model->alias])) {

                $token = $token[$model->alias];

            }

            if (!empty($token[$this->settings[$model->alias]['fields']['oauth_token']])
            && !empty($token[$this->settings[$model->alias]['fields']['oauth_token_secret']])) {

                // -- get from array
                $secret = $token[$this->settings[$model->alias]['fields']['oauth_token_secret']];
                $token  = $token[$this->settings[$model->alias]['fields']['oauth_token']];
            }

        }

        return $this->DataSource->setToken($token, $secret);

    }

    /**
     * set OAuth Access Token By Id
     *
     * @param AppModel $model
     * @param mixed    $id
     * @return true|false
     */
    public function twitterSetTokenById($model, $id = null) {

        if (is_null($id)) {

            $id = $model->id;

        }

        $data = $model->read($this->settings[$model->alias]['fields'], $id);

        if (empty($data[$model->alias])) {

            return false;

        }

        return $this->twitterSetToken($model, $data[$model->alias]);

    }

    /**
     * set OAuth Access Token By Id
     *
     * @param AppModel $model
     * @param mixed    $id
     * @return true|false
     */
    public function twitterSaveToken($model, $id = null) {

        if (is_null($id)) {

            $id = $model->id;

        }

        $data = array($model->alias => array());
        $data[$model->alias][$this->settings[$model->alias]['fields']['oauth_token']] = $this->DataSource->oauth_token;
        $data[$model->alias][$this->settings[$model->alias]['fields']['oauth_token_secret']] = $this->DataSource->oauth_token_secret;

        return $model->save($data);

    }

    /**
     * create save data
     *
     * @param  AppModel $model
     * @param  array    $token
     * @return array
     */
    public function createSaveDataByToken($model, $token) {

        $data = array(
        $model->alias => array(
                'id' => $token['user_id'],
                'username' => $token['screen_name'],
                'password' => Security::hash($token['oauth_token']),
                $this->settings[$model->alias]['fields']['oauth_token'] => $token['oauth_token'],
                $this->settings[$model->alias]['fields']['oauth_token_secret'] => $token['oauth_token_secret'],
        ),
        );

        return $data;
    }
}