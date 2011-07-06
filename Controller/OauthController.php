<?php
/**
 * TwitterKit Oauth Controller
 *
 * for CakePHP 2.0+
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
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.controller
 * @since      TwitterKit 1.0
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/
class OauthController extends AppController {

    public $uses = array();

    public $components = array('TwitterKit.Twitter');

    /**
     *
     * @var TwitterComponent
     */
    public $Twitter;

    /**
     *
     * @var AuthComponent
     */
    public $Auth;

    /**
     * (non-PHPdoc)
     * @see cake/libs/controller/Controller#beforeFilter()
     */
    public function beforeFilter()
    {
        parent::beforeFilter();

        if ($this->Components->attached('Auth')) {

            $this->Auth->allow('authorize_url', 'authenticate_url', 'callback');

        }
    }

    /**
     * get authorize url
     *
     * @param string $datasource
     */
    public function authorize_url($datasource = null) {

        Configure::write('debug', 0);
        $this->layout = 'ajax';

        // -- set datasource
        $this->Twitter->setTwitterSource($datasource);

        // set Authorize Url
        $this->set('url', $this->Twitter->getAuthorizeUrl(null, true));
    }

    /**
     * get authenthicate url
     *
     * @param string $datasource
     */
    public function authenticate_url($datasource = null) {

        Configure::write('debug', 0);
        $this->layout = 'ajax';

        // -- set datasource
        $this->Twitter->setTwitterSource($datasource);

        // set Authenticate Url
        $this->set('url', $this->Twitter->getAuthenticateUrl(null, true));
    }


    /**
     * OAuth callback
     */
    public function callback($datasource = null)
    {
        $this->Twitter->setTwitterSource($datasource);

        // 正当な返り値かチェック
        if (!$this->Twitter->isRequested()) {
            $this->Twitter->deleteAuthorizeCookie();
            $this->flash(__d('twitter_kit', 'Authorization failure.'), '/', 5);
            return;
        }

        // $tokenを取得
        $token = $this->Twitter->getAccessToken();

        if (is_string($token)) {

            $this->flash(__d('twitter_kit', 'Authorization Error: %s', $token), '/', 5);
            return;

        }

        if (class_exists('TwitterUser') || ((true || App::uses('TwitterUser', 'Model')) && class_exists('TwitterUser'))) {
            /* @var $model TwitterUser */
            $model = ClassRegistry::init('TwitterUser');
        } else {
            /* @var $model TwitterKitUser */
            $model = ClassRegistry::init('TwitterKit.TwitterKitUser');
        }

        // 保存データの作成
        $data = $model->createSaveDataByToken($token);

        if (!$model->save($data)) {
            $this->flash(__d('twitter_kit', 'The user could not be saved'), array('plugin' => 'twitter_kit', 'controller' => 'users', 'action' => 'login'), 5);
            return;
        }

        $this->Auth->login($data);

        // Redirect
        if (ini_get('session.referer_check') && env('HTTP_REFERER')) {
            $this->flash(__d('twiter_kit', 'Redirect to %s', Router::url($this->Auth->redirect(), true) . ini_get('session.referer_check')), $this->Auth->redirect(), 0);
            return;
        }

        $this->redirect($this->Auth->redirect());

    }

}
