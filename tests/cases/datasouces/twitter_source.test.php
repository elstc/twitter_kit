<?php

/**
 * Twitter API Datasource Test Case
 *
 * for CakePHP 1.3+
 * PHP version 5.2+
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
 * @subpackage twitter_kit.tests.cases.datasources
 * @since      TwitterKit 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
App::import('Datasource', 'TwitterKit.TwitterSource');
App::import('Model', array('AppModel', 'Model'));
App::import('Core', array('Router'));

ConnectionManager::create('test_twitter_source', array(
	'datasource' => 'TwitterKit.TwitterSource',
	'oauth_consumer_key' => 'cvEPr1xe1dxqZZd1UaifFA',
	'oauth_consumer_secret' => 'gOBMTs7Rw4Z3p5EhzqBey8ousRTwNDvreJskN8Z60',
));

class TestModel extends CakeTestModel {

	public $name = 'TestModel';

	public $useDbConfig = 'test_twitter_source';

	public $useTable = false;

}

class TwitterSourceTestCase extends CakeTestCase {

/**
 *
 * @var TwitterSource
 */
	public $TestSource;

/**
 *
 * @var TestModel
 */
	public $TestModel;

	function startTest() {
		$this->TestSource = ConnectionManager::getDataSource('test_twitter_source');

		$this->TestModel = new TestModel();
	}

	function endTest() {
		unset($this->TestSource);
		unset($this->TestModel);
	}

	function testInit() {
		$this->assertIsA($this->TestSource, 'TwitterSource');
		$this->assertIsA($this->TestModel->getDataSource(), 'TwitterSource');
	}

	function testOauthRequestToken() {
		$result = $this->TestSource->oauth_request_token(Router::url('/twitter_kit/callback', true));

		$this->assertTrue(is_array($result));
		$this->assertTrue(is_string($result['oauth_token']));
		$this->assertTrue(is_string($result['oauth_token_secret']));
		$this->assertTrue(is_string($result['oauth_callback_confirmed']));
		$this->assertEqual($result['oauth_token'], $this->TestSource->oauth_token);
	}

	function testOauthAuthorize() {
		$result = $this->TestSource->oauth_authorize('dummy_token');
		$this->assertEqual('http://api.twitter.com/oauth/authorize?oauth_token=dummy_token', $result);

		$token = $this->TestSource->oauth_request_token(Router::url('/twitter_kit/callback', true));
		$result = $this->TestSource->oauth_authorize();
		$this->assertEqual('http://api.twitter.com/oauth/authorize?oauth_token=' . $token['oauth_token'], $result);
	}

	function testOauthAuthenticate() {
		$result = $this->TestSource->oauth_authenticate('dummy_token');
		$this->assertEqual('http://api.twitter.com/oauth/authenticate?oauth_token=dummy_token', $result);

		$token = $this->TestSource->oauth_request_token(Router::url('/twitter_kit/callback', true));
		$result = $this->TestSource->oauth_authenticate();
		$this->assertEqual('http://api.twitter.com/oauth/authenticate?oauth_token=' . $token['oauth_token'], $result);
	}

	function testOauthAccessToken() {
		return $this->skipIf(true);

		$requestToken = $this->TestSource->oauth_request_token(Router::url('/openlist/twitter_kit/callback', true));
		$authUrl = $this->TestSource->oauth_authorize();

		debug($authUrl);

		$url = 'http://localhost/openlist/twitter_kit/callback?oauth_token=ly4DCCcq4gddZMuFNp7vbJgQiSna7Hoq4Xd7CuGOOk&oauth_verifier=Nvnw5OnMkVFv5S4tjLvKLLmsMbDyKEM92HeEILC6u7g';

		$oauth_token = 'ly4DCCcq4gddZMuFNp7vbJgQiSna7Hoq4Xd7CuGOOk';
		$oauth_verifier = 'Nvnw5OnMkVFv5S4tjLvKLLmsMbDyKEM92HeEILC6u7g';

		$token = $this->TestSource->oauth_access_token($oauth_token, $oauth_verifier);

		if (is_string($token)) {

			$this->assertEqual('Invalid / expired Token', $token);
		} else {

			$this->assertTrue(is_array($token));
			$this->assertTrue(is_string($token['oauth_token']));
			$this->assertTrue(is_string($token['oauth_token_secret']));
			$this->assertTrue(is_string($token['user_id']));
			$this->assertTrue(is_string($token['screen_name']));
			$this->assertEqual($token['oauth_token'], $this->TestSource->oauth_token);
			$this->assertEqual($token['oauth_token_secret'], $this->TestSource->oauth_token_secret);
		}
	}

	function testSetToken() {
		$this->TestSource->reset();
		$result = $this->TestSource->setToken('');
		$this->assertFalse($result);
		$this->assertEqual('', $this->TestSource->oauth_token);
		$this->assertEqual('', $this->TestSource->oauth_token_secret);

		$this->TestSource->reset();
		$result = $this->TestSource->setToken(array('oauth_token' => 'dummy_token', 'oauth_token_secret' => 'dummy_secret'));
		$this->assertTrue($result);
		$this->assertEqual('dummy_token', $this->TestSource->oauth_token);
		$this->assertEqual('dummy_secret', $this->TestSource->oauth_token_secret);

		$this->TestSource->reset();
		$this->assertEqual('', $this->TestSource->oauth_token);
		$this->assertEqual('', $this->TestSource->oauth_token_secret);

		$result = $this->TestSource->setToken('dummy_token2', 'dummy_secret2');
		$this->assertTrue($result);
		$this->assertEqual('dummy_token2', $this->TestSource->oauth_token);
		$this->assertEqual('dummy_secret2', $this->TestSource->oauth_token_secret);
	}

	function testGetAnywhereIdentity() {
		$this->assertEqual($this->TestSource->getAnywhereIdentity(15982041), '15982041:7f25bf8e58f67fb01857dee740169456ee65a885');
	}

}
