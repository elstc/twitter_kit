<?php
/**
 * TwitterKit Oauth Controller
 *
 * for CakePHP 1.3+
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
            
            $this->Auth->allow('authorize_url', 'authenticate_url');
            
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
        $this->set('url', $this->Twitter->getAuthorizeUrl());
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
        $this->set('url', $this->Twitter->getAuthenticateUrl());
    }
}
