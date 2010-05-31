<?php
App::import('Behavior', 'TwitterKit.Twitter');
/**
 * TwitterKit Twitter DirectMessage Behavior
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
class TwitterDirectMessageBehavior extends TwitterBehavior {

    /**
     * send DirectMessage
     *
     * @param AppModel $model
     * @param string $to
     * @param string $message
     */
    public function sendDirectMessage($model, $to, $message)
    {
        $result = $this->getTwitterSource($model)->direct_messages_new(array('user' => $to, 'text' => $message));
        return $result;
    }


}