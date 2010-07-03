<?php
/**
 * Twitter API Behavior Test Case
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
App::import('Core', array('AppModel', 'Model'));
App::import('Behavior', array('TwitterKit.Twitter'));

ConnectionManager::create('twitter',
array(
        'datasource' => 'TwitterKit.TwitterSource',
        'oauth_consumer_key'    => 'cvEPr1xe1dxqZZd1UaifFA',
        'oauth_consumer_secret' => 'gOBMTs7Rw4Z3p5EhzqBey8ousRTwNDvreJskN8Z60',
) );

ConnectionManager::create('test_twitter',
array(
        'datasource' => 'TwitterKit.TwitterSource',
        'oauth_consumer_key'    => 'cvEPr1xe1dxqZZd1UaifFA',
        'oauth_consumer_secret' => 'gOBMTs7Rw4Z3p5EhzqBey8ousRTwNDvreJskN8Z60',
) );

ConnectionManager::create('fake_twitter',
array(
        'datasource' => 'TwitterKit.TwitterSource',
        'oauth_consumer_key'    => 'cvEPr1xe1dxqZZd1UaifFA',
        'oauth_consumer_secret' => 'gOBMTs7Rw4Z3p5EhzqBey8ousRTwNDvreJskN8Z60',
) );

Mock::generate('AppModel', 'MockModel');

class MockTwitterTestModel extends AppModel {

    public $name = 'MockTwitterTestModel';

    public $useTable = false;

}

class TwitterBehaviorTest extends CakeTestCase
{

    public $fixtures = array('plugin.twitter_kit.user_token');
     
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
        $this->TestModel = ClassRegistry::init('MockTwitterTestModel');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter', array('datasource' => 'test_twitter'));
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
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


    function testSetup()
    {
        $model = new MockTwitterTestModel();

        $model->Behaviors->attach('TwitterKit.Twitter');
        $this->assertEqual('twitter', $model->Behaviors->Twitter->settings['MockTwitterTestModel']['datasource']);
        $this->assertEqual('oauth_token', $model->Behaviors->Twitter->settings['MockTwitterTestModel']['fields']['oauth_token']);
        $this->assertEqual('oauth_token_secret', $model->Behaviors->Twitter->settings['MockTwitterTestModel']['fields']['oauth_token_secret']);

        $this->TestModel->Behaviors->detach('TwitterKit.Twitter');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter', array('datasource' => 'test_twitter'));
        $this->assertEqual('test_twitter', $model->Behaviors->Twitter->settings['MockTwitterTestModel']['datasource']);

        $this->TestModel->Behaviors->detach('TwitterKit.Twitter');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter', array('fields' => array('oauth_token' => 'access_token', 'oauth_token_secret' => 'access_token_secret')));
        $this->assertEqual('access_token', $model->Behaviors->Twitter->settings['MockTwitterTestModel']['fields']['oauth_token']);
        $this->assertEqual('access_token_secret', $model->Behaviors->Twitter->settings['MockTwitterTestModel']['fields']['oauth_token_secret']);
    }

    function testGetTwitterSource()
    {
        $this->assertIsA($this->TestModel->getTwitterSource(), 'TwitterSource');
        $this->assertIsA($this->TestModel->Behaviors->Twitter->DataSource, 'TwitterSource');
    }

    function testSetTwitterSource()
    {
        $this->TestModel->setTwitterSource('fake_twitter');
        $this->assertEqual($this->TestModel->Behaviors->Twitter->DataSource->configKeyName, 'fake_twitter');

        $this->TestModel->setTwitterSource('not_defined');
        $this->assertEqual($this->TestModel->Behaviors->Twitter->DataSource->configKeyName, 'fake_twitter');

    }

    function testTwitterAuthorizedUrl()
    {
        $callback = Router::url('/twitter_kit/oauth/callback', true);
        $result = $this->TestModel->twitterAuthorizeUrl($callback);
        $this->assertPattern('!http://api\.twitter\.com/oauth/authorize\?oauth_token=.+!', $result);

        // for testGetOAuthAccessToken
        debug($result);
    }

    function testTwitterAuthenticateUrl()
    {
        $callback = Router::url('/twitter_kit/oauth/callback', true);
        $result = $this->TestModel->twitterAuthenticateUrl($callback);
        $this->assertPattern('!http://api\.twitter\.com/oauth/authenticate\?oauth_token=.+!', $result);
    }

    function testTwitterAccessToken()
    {
        $result = $this->TestModel->twitterAccessToken();
        $this->assertFalse($result);

        $oauth_token    = 'WQPT4PonLPUD2FNycXTZxxTEXFZrEsIZEMfADivd9g';
        $oauth_verifier = 'AMmHw1rexLssI2XOXPiLstxWfYujinuLHoTTAJKmw';

        $result =  $this->TestModel->twitterAccessToken($oauth_token, $oauth_verifier);

        debug($result);

        if (!is_string($result)) {

            $this->assertIsA($result['oauth_token'], 'String');
            $this->assertIsA($result['oauth_token_secret'], 'String');
            $this->assertIsA($result['user_id'], 'String');
            $this->assertIsA($result['screen_name'], 'String');

        }
    }

    function testTwitterSetToken()
    {
        $result = $this->TestModel->twitterSetToken();
        $this->assertFalse($result);
        $ds = $this->TestModel->getTwitterSource();
        $this->assertEqual('', $ds->oauth_token);
        $this->assertEqual('', $ds->oauth_token_secret);

        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $result = $this->TestModel->twitterSetToken(array('oauth_token' => 'dummy_token', 'oauth_token_secret' => 'dummy_secret'));
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('dummy_token',  $ds->oauth_token);
        $this->assertEqual('dummy_secret', $ds->oauth_token_secret);

        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $ds = $this->TestModel->getTwitterSource();
        $this->assertEqual('', $ds->oauth_token);
        $this->assertEqual('', $ds->oauth_token_secret);

        $result = $this->TestModel->twitterSetToken('dummy_token2', 'dummy_secret2');
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('dummy_token2',  $ds->oauth_token);
        $this->assertEqual('dummy_secret2', $ds->oauth_token_secret);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $this->TestModel->data = array('MockTwitterTestModel' => array('oauth_token' => 'dummy_token3', 'oauth_token_secret' => 'dummy_secret3'));
        $result = $this->TestModel->twitterSetToken();
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('dummy_token3',  $ds->oauth_token);
        $this->assertEqual('dummy_secret3', $ds->oauth_token_secret);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $this->TestModel->Behaviors->detach('TwitterKit.Twitter');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter', array('fields' => array('oauth_token' => 'access_token', 'oauth_token_secret' => 'access_token_secret')));
        $this->TestModel->data = array('MockTwitterTestModel' => array('access_token' => 'dummy_token4', 'access_token_secret' => 'dummy_secret4'));
        $result = $this->TestModel->twitterSetToken();
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('dummy_token4',  $ds->oauth_token);
        $this->assertEqual('dummy_secret4', $ds->oauth_token_secret);
    }

    function testTwitterSetTokenById()
    {
        $this->loadFixtures('UserToken');
        $this->TestModel = ClassRegistry::init('UserToken');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter');

        $result = $this->TestModel->twitterSetTokenById();
        $ds = $this->TestModel->getTwitterSource();
        $this->assertFalse($result);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $result = $this->TestModel->twitterSetTokenById(1);
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('oauth_token1',  $ds->oauth_token);
        $this->assertEqual('oauth_token_secret1', $ds->oauth_token_secret);

        // --
        $this->TestModel->id = 2;
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $result = $this->TestModel->twitterSetTokenById();
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('oauth_token2',  $ds->oauth_token);
        $this->assertEqual('oauth_token_secret2', $ds->oauth_token_secret);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $this->TestModel->Behaviors->detach('TwitterKit.Twitter');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter', array('fields' => array('oauth_token' => 'access_token', 'oauth_token_secret' => 'access_token_secret')));
        $result = $this->TestModel->twitterSetTokenById(1);
        $ds = $this->TestModel->getTwitterSource();
        $this->assertTrue($result);
        $this->assertEqual('access_token1',  $ds->oauth_token);
        $this->assertEqual('access_token_secret1', $ds->oauth_token_secret);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $result = $this->TestModel->twitterSetTokenById(3);
        $ds = $this->TestModel->getTwitterSource();
        $this->assertFalse($result);
        $this->assertEqual('',  $ds->oauth_token);
        $this->assertEqual('', $ds->oauth_token_secret);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $result = $this->TestModel->twitterSetTokenById(4);
        $ds = $this->TestModel->getTwitterSource();
        $this->assertFalse($result);
    }

    function testTwitterSaveToken()
    {

        $this->loadFixtures('UserToken');
        $this->TestModel = ClassRegistry::init('UserToken');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter');

        // --
        $this->TestModel->id = 4;
        $this->TestModel->twitterSetToken('dummy_token4', 'dummy_secret4');
        $result = $this->TestModel->twitterSaveToken();
        $this->assertTrue($result);
        $data = $this->TestModel->read();
        $this->assertEqual('dummy_token4', $data['UserToken']['oauth_token']);
        $this->assertEqual('dummy_secret4', $data['UserToken']['oauth_token_secret']);

        // --
        $this->TestModel->Behaviors->Twitter->DataSource->reset();
        $this->TestModel->Behaviors->detach('TwitterKit.Twitter');
        $this->TestModel->Behaviors->attach('TwitterKit.Twitter', array('fields' => array('oauth_token' => 'access_token', 'oauth_token_secret' => 'access_token_secret')));
        $this->TestModel->twitterSetToken('dummy_token5', 'dummy_secret5');
        $result = $this->TestModel->twitterSaveToken(5);
        $this->assertTrue($result);
        $data = $this->TestModel->read();
        $this->assertEqual('dummy_token5', $data['UserToken']['access_token']);
        $this->assertEqual('dummy_secret5', $data['UserToken']['access_token_secret']);
    }
}