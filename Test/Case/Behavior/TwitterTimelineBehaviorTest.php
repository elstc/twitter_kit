<?php
/**
 * TwitterTimelineBehavior Test Case
 *
 * for CakePHP 1.3.0
 * PHP version 5
 *
 * Copyright 2010, ELASTIC Consultants Inc. (http://elasticconsultants.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @version    1.0
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010, ELASTIC Consultants Inc.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.tests.cases.behaviors
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');
App::uses('TwitterTimelineBehavior', 'TwitterKit.Model/Behavior');
App::uses('ConnectionManager', 'Model');

ConnectionManager::create('test_twitter',
array(
        'datasource' => 'TwitterKit.TwitterSource',
        'oauth_consumer_key'    => 'ePqJkNG4cSyNePJnOjAQw',
        'oauth_consumer_secret' => 'dfezTpMTxbhE3UBBKmtQNwR0EvVceqKHBOCKmcLQ',
        'oauth_token'        => '152934772-jg6iF2hDhkBEyP8f0gSuQDD1TH0cLptET8fOC2UW',
        'oauth_token_secret' => 'pgMDC7zO6cTg0p2cIlTZU4dt4nEmhHqma6SkeprEA',
        'api_key' => 'ePqJkNG4cSyNePJnOjAQw',
) );


class MockTwitterTimelineTestModel extends AppModel {

    public $name = 'MockTwitterTimelineTestModel';

    public $useTable = false;

}

class TwitterTimelineBehaviorTest extends CakeTestCase
{

    public $autoFixtures = false;

    /**
     *
     * @var AppModel
     */
    public $TestModel;

    /**
     * startTest method
     *
     * @access public
     * @return void
     */
    function startTest()
    {
        $this->TestModel = ClassRegistry::init('MockTwitterTimelineTestModel');
        $this->TestModel->Behaviors->attach('TwitterKit.TwitterTimeline', array('datasource' => 'test_twitter'));
        $this->TestModel->Behaviors->TwitterTimeline->DataSource->reset();
    }

    /**
     * endTest method
     *
     * @access public
     * @return void
     */
    function endTest()
    {
        unset($this->TestModel);
        ClassRegistry::flush();
    }


    function testGetUserTimeline()
    {
        $result = $this->TestModel->getUserTimeline();
        foreach ($result as $tweet) {
            $this->assertEqual($tweet['user']['id'], 152934772);
        }

        // -- change user
        $result = $this->TestModel->getUserTimeline('nojimage');
        foreach ($result as $tweet) {
            $this->assertEqual($tweet['user']['id'], 15982041);
        }

        $this->TestModel->data = array( $this->TestModel->alias => array($this->TestModel->primaryKey => 'nojimage'));
        $result = $this->TestModel->getUserTimeline();
        foreach ($result as $tweet) {
            $this->assertEqual($tweet['user']['id'], 15982041);
        }

        // -- limit
        $result = $this->TestModel->getUserTimeline(array('id' => 'nojimage', 'count' => 10));
        $this->assertTrue(count($result) <= 10);

        // -- max_id
        $last_id = $result[count($result) - 1]['id_str'];
        $result = $this->TestModel->getUserTimeline(array('id' => 'nojimage', 'max_id' => $last_id));
        $this->assertEqual($result[0]['id'], $last_id);

        // -- paging
        $next_id = $result[1]['id'];
        $result = $this->TestModel->getUserTimeline(array('id' => 'nojimage', 'count' => 10, 'page' => 2));
        $this->assertEqual($result[0]['id'], $next_id);

        // -- exclude reply
        $result = $this->TestModel->getUserTimeline(array('id' => 'nojimage', 'page' => 2, 'exclude_reply' => true));
        foreach ($result as $tweet) {
            $this->assertPattern('/^[^@]/u', $tweet['text'], $tweet['text']);
        }
    }
}