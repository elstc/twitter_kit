<?php
/**
 * TwitterKit Users Controller
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
class UsersController extends TwitterKitAppController {

    public $name = 'Users';

    public $helpers = array('Html', 'Form', 'Js');

    /**
     * (non-PHPdoc)
     * @see cake/libs/controller/Controller#beforeFilter()
     */
    public function beforeFilter()
    {
        $this->Auth->allow('logout');
        parent::beforeFilter();
    }

    public function login()
    {
        $this->Session->destroy(); // TODO: remove
    }

    public function logout()
    {
        $this->Session->destroy();
        $this->Session->setFlash(__('ログアウトしました。', true));
        $this->redirect('/');
    }

}
