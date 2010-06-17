<?php
App::import('Behavior', 'TwitterKit.Twitter');
/**
 * TwitterKit Twitter Timeline Behavior
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
class TwitterTimelineBehavior extends TwitterBehavior {

    /**
     * get user timeline
     *
     * @param AppModel $model
     * @param mixed    $options
     */
    function getUserTimeline($model, $options = array()) {

        if (is_scalar($options)) {
            $options = array('id' => $options);
        }

        $defaults = array(
            'exclude_reply' => false,
            'skip_user'     => true,
        );

        $options = am($defaults, $options);

        $exclude_reply = $options['exclude_reply'];
        unset($options['exclude_reply']);

        // -- set model data id
        if (empty($options['id']) && !empty($model->data[$model->alias][$model->primaryKey])) {
            $options['id'] = $model->data[$model->alias][$model->primaryKey];
        }

        // -- get timeline
        $timeline = $this->DataSource->statuses_user_timeline($options);

        // -- eclude replay
        if ($exclude_reply) {
            $timeline = array_values(array_filter($timeline, array($this, '_exculudeReply')));
        }

        return $timeline;
    }

    /**
     * filter reply
     *
     * @return boolean
     */
    function _exculudeReply($data) {
        return preg_match('/^[^@]/u', $data['text']);
    }
}