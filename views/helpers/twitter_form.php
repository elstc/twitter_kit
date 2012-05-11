<?php

App::import('Helper', 'TwitterKit.Twitter');

/**
 * TwitteKit TwitterForm Helper
 *
 * Copyright 2011, ELASTIC Consultants Inc. http://elasticconsultants.com/
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.1
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2011 ELASTIC Consultants Inc.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.views.helpers
 * @since      File available since Release 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 * @deprecated
 */
class TwitterFormHelper extends AppHelper {

	public $helpers = array('TwitterKit.Twitter');

/**
 *
 * @var TwitterHelper
 */
	public $Twitter;

/**
 * create tweet box
 *
 * @param $fieldName
 * @param $options
 *      type: element type (default: textarea)
 *      maxLength:   text max length (default: 140)
 *      counterText: length message
 *      submit: submit button message. if set to false, not create.
 *      jqueryCharCount: path to charCount.js (jquery plugin)
 *      other keys set to input element options.
 */
	public function tweet($fieldName, $options = array()) {
		return $this->Twitter->tweet($fieldName, $options);
	}

/**
 * create OAuth Link
 *
 * @param $options
 *  loading:      loading message
 *  login:        login link text
 *  datasource:   datasource name (default: twitter)
 *  authenticate: use authenticate link (default: false)
 */
	public function oauthLink($options = array()) {
		return $this->Twitter->oauthLink($options);
	}

/**
 * linkify text
 *
 * @param string $value
 * @param array  $options
 *    username: linkify username. eg. @username
 *    hashtag : linkify hashtag. eg. #hashtag
 *    url     : linkify url. eg. http://example.com/
 * @return string
 */
	public function linkify($value, $options = array()) {
		return $this->Twitter->linkify($value, $options);
	}

}