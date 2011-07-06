<?php
App::uses('Twitter', 'TwitterKit.Model/Behavior');
/**
 * TwitterKit Twitter Tweet Behavior
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
class TwitterTweetBehavior extends TwitterBehavior {

    /**
     * update status
     *
     * @param AppModel $model
     * @param mixed $message
     * @param array $params
     */
    public function tweet($model, $message, $params = array())
    {
        if (is_string($message)) {
            $message = array('status' => $message);
        }

        $result = $this->getTwitterSource($model)->statuses_update(am($message, $params));
        return $result;
    }

    /**
     * reply message
     *
     * @param AppModel $model
     * @param string $message
     * @param string $inReplyToStatusId
     * @param array $params
     */
    public function reply($model, $message, $inReplyToStatusId, $params = array())
    {
        if (is_string($message)) {
            $message = array('status' => $message);
        }

        $message = am($message, array('in_reply_to_status_id' => $inReplyToStatusId));

        $result = $this->getTwitterSource($model)->statuses_update(am($message, $params));
        return $result;
    }

    /**
     * post retweet
     *
     * @param AppModel $model
     * @param sting $id
     * @param array $params
     */
    public function retweet($model, $id, $params = array())
    {
        $result = $this->getTwitterSource($model)->statuses_retweet($id, $params);
        return $result;
    }

}