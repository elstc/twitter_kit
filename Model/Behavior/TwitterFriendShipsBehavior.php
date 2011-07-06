<?php
App::uses('Twitter', 'TwitterKit.Model/Behavior');
/**
 * TwitterKit Twitter Friendships Behavior
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
 * @subpackage twitter_kit.models.behaviors
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 * @deprecated
 */
class TwitterFriendshipsBehavior extends TwitterBehavior {

    /**
     * follow user
     *
     * @param AppModel $model
     * @param string $id user id or screen name
     */
    public function follow($model, $id)
    {
        $result = $this->getTwitterSource($model)->friendships_create($id);
        return $result;
    }

}