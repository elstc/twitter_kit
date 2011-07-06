<?php
/**
 * TwitterKit TwitterUser Model
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
 * @subpackage twitter_kit.models
 * @since      TwitterKit 1.0
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/
class TwitterKitUser extends TwitterKitAppModel {

    public $name = 'TwitterKitUser';

    public $alias = 'TwitterUser';

    public $useTable = 'twitter_users';

    public $displayField = 'username';

    public $validate = array(
		'username' => array(
			'notempty' => array('rule' => array('notempty'))));

    public $actsAs = array(
        'TwitterKit.Twitter',
    );

}
