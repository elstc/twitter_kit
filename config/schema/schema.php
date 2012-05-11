<?php

/**
 * TwitterKit Schema
 *
 * PHP version 5
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
 * @subpackage twitter_kit.config.schema
 * @since      TwitterKit 1.0
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TwitterKitSchema extends CakeSchema {

	public $name = 'TwitterKit';

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
		//
	}

	public $twitter_users = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 20, 'key' => 'primary'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'username' => array('type' => 'string', 'null' => false, 'default' => null, 'key' => 'index'),
		'password' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 40),
		'oauth_token' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
		'oauth_token_secret' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 128),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'IX_username' => array('column' => 'username', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
	);

}
