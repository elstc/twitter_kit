<?php
/**
 * TwitterKit Oauth Controller
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
        if (!empty($this->Auth) && is_object($this->Auth)) {

            $this->Auth->allow('authorize_url', 'authenticate_url', 'callback');

        }

        parent::beforeFilter();
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
        if (empty($this->params['url']['oauth_token']) || empty($this->params['url']['oauth_verifier'])) {
            $this->Twitter->deleteAuthorizeCookie();
            $this->flash(__('認証に失敗しました', true), '/', 5);
            return;
        }

        // $tokenを取得
        $token = $this->Twitter->getAccessToken();

        if (is_string($token)) {

            $this->flash(__('認証エラー: ', true) . $token, '/', 5);
            return;

        }

        /* @var $model TwitterKitUser */
        $model = ClassRegistry::init('TwitterKit.TwitterKitUser');

        // 保存データの作成
        $data[$model->alias] = array(
            'id' => $token['user_id'],
            'username' => $token['screen_name'],
            'password' => Security::hash($token['oauth_token']),
            'oauth_token' => $token['oauth_token'],
            'oauth_token_secret' => $token['oauth_token_secret'],
        );

        if (!$model->save($data)) {
            $this->flash(__('ユーザ情報の保存に失敗しました', true), array('plugin' => 'twitter_kit', 'controller' => 'users', 'action' => 'login'), 5);
            return;
        }

        $this->Auth->login($data);

        // Redirect
        $this->redirect('/');
    }

}
