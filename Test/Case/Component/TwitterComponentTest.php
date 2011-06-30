<?php
/**
 * Twitter API Component Test Case
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
 * @subpackage twitter_kit.tests.cases.components
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
App::uses('TwitterComponent', 'TwitterKit.Controller/Component');
App::uses('AppController', 'Controller');
App::uses('ConnectionManager', 'Model');
App::uses('Controller', 'Controller');
App::uses('Component', 'Controller');

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


class MockTwitterTestController extends Controller
{

    public $components = array(
        'TwitterKit.Twitter' => array(
            'datasource' => 'test_twitter',
        ),
    );
    public $stoped = false;

    public $status = 200;

    public $headers = array();

    function _stop($status = 0)
    {
        $this->stoped = $status;
    }

    function redirect($url, $status = null, $exit = true) {
        $this->status = $status;
    }

    function header($status) {
        $this->headers[] = $status;
    }
}

/**
 * @author nojima
 *
 */
class TwitterTestCase extends CakeTestCase
{

    /**
     * @var TestComponent
     */
    public $TestComponent;

    function startTest()
    {
        $this->_reset();
    }

    function endTest()
    {
        unset($this->TestComponent);
        ClassRegistry::flush();
    }

    function _reset($config = null)
    {
        $Controller = new MockTwitterTestcontroller(new CakeRequest);
        if ($config !== null) {
            $Controller->components['TwitterKit.Twitter'] = $config;
        }
        $Controller->constructClasses();
        $this->TestComponent = $Controller->Twitter;
        $this->TestComponent->DataSource->reset();
        $Controller->startupProcess();
    }


    function testConstruct()
    {

        $this->_reset(array());
        $this->assertIsA($this->TestComponent, 'TwitterComponent');
        $this->assertEqual('twitter', $this->TestComponent->settings['datasource']);
        $this->assertEqual('oauth_token', $this->TestComponent->settings['fields']['oauth_token']);
        $this->assertEqual('oauth_token_secret', $this->TestComponent->settings['fields']['oauth_token_secret']);

        $this->_reset(array('datasource' => 'test_twitter'));
        $this->assertEqual('test_twitter', $this->TestComponent->settings['datasource']);

        $this->_reset(array('fields' => array('oauth_token' => 'access_token', 'oauth_token_secret' => 'access_token_secret')));
        $this->assertEqual('access_token', $this->TestComponent->settings['fields']['oauth_token']);
        $this->assertEqual('access_token_secret', $this->TestComponent->settings['fields']['oauth_token_secret']);

    }

    function testGetTwitterSource()
    {

        $this->assertIsA($this->TestComponent->DataSource, 'TwitterSource');
        $this->assertIsA($this->TestComponent->getTwitterSource(), 'TwitterSource');

    }

    function testSetDataSource()
    {

        $this->TestComponent->setTwitterSource('fake_twitter');
        $this->assertEqual($this->TestComponent->DataSource->configKeyName, 'fake_twitter');

        $this->TestComponent->setTwitterSource('not_defined');
        $this->assertEqual($this->TestComponent->DataSource->configKeyName, 'fake_twitter');
    }

    function testGetAuthorizedUrl()
    {
        $callback = Router::url('/twitter_kit/oauth/callback', true);
        $result = $this->TestComponent->getAuthorizeUrl($callback);
        $this->assertPattern('!http://api\.twitter\.com/oauth/authorize\?oauth_token=.+!', $result);

        // for testGetAccessToken
        debug($result);
    }

    function testGetAuthenticateUrl()
    {
        $callback = Router::url('/twitter_kit/oauth/callback', true);
        $result = $this->TestComponent->getAuthenticateUrl($callback);
        $this->assertPattern('!http://api\.twitter\.com/oauth/authenticate\?oauth_token=.+!', $result);
    }

    function testGetAccessToken()
    {
        $result = $this->TestComponent->getAccessToken();
        $this->assertFalse($result);

        $controller = new MockTwitterTestController();

        $this->TestComponent->controller->request->query = array(
            'oauth_token' => 'vkwlQH1uLWWahUNa7PNE6RbBTYGotugP9wh3NSoT0',
            'oauth_verifier' => 'DUWU7DpwCGYNgKbq1B9Pf3uhwVDLyv9XvTP3T3DVAo',
        );
        $result =  $this->TestComponent->getAccessToken();

        debug($result);

        if (!is_string($result)) {

            $this->assertIsA($result['oauth_token'], 'String');
            $this->assertIsA($result['oauth_token_secret'], 'String');
            $this->assertIsA($result['user_id'], 'String');
            $this->assertIsA($result['screen_name'], 'String');

        }
    }

    function testSetToken()
    {
        $result = $this->TestComponent->setToken('');
        $this->assertFalse($result);
        $this->assertEqual('', $this->TestComponent->DataSource->oauth_token);
        $this->assertEqual('', $this->TestComponent->DataSource->oauth_token_secret);

        $this->TestComponent->DataSource->reset();
        $result = $this->TestComponent->setToken(array('oauth_token' => 'dummy_token', 'oauth_token_secret' => 'dummy_secret'));
        $this->assertTrue($result);
        $this->assertEqual('dummy_token',  $this->TestComponent->DataSource->oauth_token);
        $this->assertEqual('dummy_secret', $this->TestComponent->DataSource->oauth_token_secret);

        $this->TestComponent->DataSource->reset();
        $this->assertEqual('', $this->TestComponent->DataSource->oauth_token);
        $this->assertEqual('', $this->TestComponent->DataSource->oauth_token_secret);

        $result = $this->TestComponent->setToken('dummy_token2', 'dummy_secret2');
        $this->assertTrue($result);
        $this->assertEqual('dummy_token2',  $this->TestComponent->DataSource->oauth_token);
        $this->assertEqual('dummy_secret2', $this->TestComponent->DataSource->oauth_token_secret);
    }

    function testSetTokenByUser()
    {
        $user   = array(
            'User' => array(
                'oauth_token' => 'dummy_token',
                'oauth_token_secret' => 'dummy_secret',
        ));
        $result = $this->TestComponent->setTokenByUser($user);
        $this->assertEqual('dummy_token', $this->TestComponent->DataSource->oauth_token);
        $this->assertEqual('dummy_secret', $this->TestComponent->DataSource->oauth_token_secret);

        $this->TestComponent->settings['fields']['oauth_token'] = 'accsess_token';
        $this->TestComponent->settings['fields']['oauth_token_secret'] = 'accsess_token_secret';

        $user   = array(
            'User' => array(
                'accsess_token' => 'dummy_token2',
                'accsess_token_secret' => 'dummy_secret2',
        ));
        $result = $this->TestComponent->setTokenByUser($user);
        $this->assertEqual('dummy_token2', $this->TestComponent->DataSource->oauth_token);
        $this->assertEqual('dummy_secret2', $this->TestComponent->DataSource->oauth_token_secret);

    }
}
