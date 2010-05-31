<?php
App::import('Behavior', 'TwitterKit.Twitter');
/**
 * TwitterKit Twitter List Behavior
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
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.models.behaviors
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterListBehavior extends TwitterBehavior {

    /**
     * check user in list
     *
     * @param AppModel $model
     * @param string $list_user
     * @param string $list_slug
     * @param string $user_id
     */
    public function existsListMember($model, $list_user, $list_slug, $user_id)
    {
        $result = $this->getTwitterSource($model)->get_list_members_id($list_user, $list_slug, $user_id);

        return isset($result['id']);
    }


}