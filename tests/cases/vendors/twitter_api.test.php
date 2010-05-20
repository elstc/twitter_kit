<?php
/**
 * Twitter API Test Case
 *
 * for CakePHP 1.3.0
 * PHP version 5
 *
 * Copyright 2010, elasticconsultants.com
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @version    1.0
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010 elasticconsultants co.,ltd.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.tests.cases.behaviors
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
App::import('Vendor', array('TwitterKit.TwitterApi'));

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


class TwitterApitTest extends CakeTestCase
{

    /**
     *
     * @var TwitterApi
     */
    public $TestObj;

    /**
     * startTest method
     *
     * @access public
     * @return void
     */
    function startTest()
    {
        $this->TestObj = ClassRegistry::init('TwitterKit.TwitterApi', 'Vendor');
        $this->TestObj->setup('default', array('datasource' => 'test_twitter'));
        $this->TestObj->DataSource->reset();
    }

    /**
     * endTest method
     *
     * @access public
     * @return void
     */
    function endTest()
    {
        unset($this->TestObj);
        ClassRegistry::flush();
    }


    function testSetup()
    {

        $key = 'default';

        $obj = new TwitterApi($key);
        $this->assertEqual('twitter', $obj->settings[$key]['datasource']);
        $this->assertEqual('oauth_token', $obj->settings[$key]['fields']['oauth_token']);
        $this->assertEqual('oauth_token_secret', $obj->settings[$key]['fields']['oauth_token_secret']);

        $obj = new TwitterApi();
        $obj->setup($key, array('datasource' => 'test_twitter'));
        $this->assertEqual('test_twitter', $obj->settings[$key]['datasource']);

        $obj = new TwitterApi();
        $obj->setup($key, array('fields' => array('oauth_token' => 'access_token', 'oauth_token_secret' => 'access_token_secret')));
        $this->assertEqual('access_token', $obj->settings[$key]['fields']['oauth_token']);
        $this->assertEqual('access_token_secret', $obj->settings[$key]['fields']['oauth_token_secret']);
    }

    function testGetTwitterSource()
    {
        $this->assertIsA($this->TestObj->getTwitterSource(), 'TwitterSource');
        $this->assertIsA($this->TestObj->DataSource, 'TwitterSource');
    }

    function testSetTwitterSource()
    {
        $this->TestObj->setTwitterSource(null, 'fake_twitter');
        $this->assertEqual($this->TestObj->DataSource->configKeyName, 'fake_twitter');

        $this->TestObj->setTwitterSource(null, 'not_defined');
        $this->assertEqual($this->TestObj->DataSource->configKeyName, 'fake_twitter');

    }

    function testGetAuthorizedUrl()
    {
        $callback = Router::url('/twitter_kit/oauth/callback', true);
        $result = $this->TestObj->getAuthorizeUrl(null, $callback);
        $this->assertPattern('!http://api\.twitter\.com/oauth/authorize\?oauth_token=.+!', $result);

        // for testGetOAuthAccessToken
        debug($result);
    }

    function testGetAuthenticateUrl()
    {
        $callback = Router::url('/twitter_kit/oauth/callback', true);
        $result = $this->TestObj->getAuthenticateUrl(null, $callback);
        $this->assertPattern('!http://api\.twitter\.com/oauth/authenticate\?oauth_token=.+!', $result);
    }

    function testGetAccessToken()
    {
        $result = $this->TestObj->getAccessToken();
        $this->assertFalse($result);

        $oauth_token    = 'WQPT4PonLPUD2FNycXTZxxTEXFZrEsIZEMfADivd9g';
        $oauth_verifier = 'AMmHw1rexLssI2XOXPiLstxWfYujinuLHoTTAJKmw';

        $result =  $this->TestObj->getAccessToken(null, $oauth_token, $oauth_verifier);

        debug($result);

        if (!is_string($result)) {

            $this->assertIsA($result['oauth_token'], 'String');
            $this->assertIsA($result['oauth_token_secret'], 'String');
            $this->assertIsA($result['user_id'], 'String');
            $this->assertIsA($result['screen_name'], 'String');

        }
    }
}