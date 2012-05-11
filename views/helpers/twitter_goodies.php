<?php

App::import('Helper', 'TwitterKit.Twitter');

/**
 * TwitteKit Twitter Goodies Helper
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
 */
class TwitterGoodiesHelper extends AppHelper {

/**
 *
 * @var TwitterHelper
 */
	public $Twitter;

/**
 * create tweet button
 *
 * @see http://dev.twitter.com/pages/tweet_button
 * @param string  $label
 * @param array   $options
 * @param boolean $dataAttribute
 * @param boolean $scriptInline
 * @return string
 */
	public function tweetButton($label = null, $options = array(), $dataAttribute = false, $scriptInline = false) {
		return $this->Twitter->tweetButton($label, $options, $dataAttribute, $scriptInline);
	}

}