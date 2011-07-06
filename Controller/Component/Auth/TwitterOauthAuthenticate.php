<?php

/**
 * TwitterKit TwitterOauth Authenticate
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
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.controllers.components
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterOauthAuthenticate extends BaseAuthenticate {

    public static $defaultSettings = array(
        'callback' => false,
    );


    /**
     *
     * @var $error Error message for authenticate result, otherwise false.
     */
    public static $error = false;

    /**
     * Constructor. prepares defaultSettings
     * Settings:
     *      userModel: model to use callback
     *      callback: string, model method or its behavior method name
     *
     * @param ComponentCollection $collection
     * @param array $settings
     */
    public function __construct(ComponentCollection $collection, $settings = array()) {
        $this->settings = Set::merge($this->settings, self::$defaultSettings);
        parent::__construct($collection, $settings);
    }

    /**
     * Authenticate callback.
     * provides oauth authentication from callback url
     *
     * @param ComponentCollection $collection
     * @param array $settings
     */
    public function authenticate(CakeRequest $request, CakeResponse $response) {

        self::$error = false;

        $Twitter = $this->_Collection->load('TwitterKit.Twitter');

        if (!$Twitter->isRequested()) {
            return false;
        }

        $token = $Twitter->getAccessToken();
        if (is_scalar($token)) {
            self::$error = $token;
            return false;
        }

        if ($this->settings['callback'] !== false) {
            $user = ClassRegistry::init($this->settings['userModel'])->{$this->settings['callback']}($token);
        } else {
            $user = $token;
        }

        return $user;

    }

}