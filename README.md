# TwitterKit Plugin for CakePHP 1.3+

This plugin provides Twitter API DataSource, Behavior, Components, with OAuth support.

Copyright 2010, ELASTIC Consultants Inc. (http://elasticconsultants.com)

## !! This plugin has been deprecated !!

This plugin is no longer being actively developed and maintained.

If you need plugin for Twitter API, please see [nojimage/CakePHP-Twim](https://github.com/nojimage/CakePHP-Twim "nojimage/CakePHP-Twim").

## Features
 * TwitterKit.TwitterSource
 * TwitterKit.TwitterBehavior
 * TwitterKit.TwitterComponent
 * TwitterKit.OauthController

## Installation

movo to APP/plugins/

    git clone http://github.com/elstc/twitter_kit.git

## Usage

### setup APP/config/database.php

    /**
     * TwitterSource for none auth
     *
     * @var array
     */
    var $twitter = array(
        'driver' => 'TwitterKit.TwitterSource',
    );

OR

    /**
     * TwitterSource using OAuth
     *
     * @var array
     */
    var $twitter = array(
        'driver' => 'TwitterKit.TwitterSource',
        'oauth_consumer_key'    => 'YOUR_CONSUMER_KEY',
        'oauth_consumer_secret' => 'YOUR_CONSUMER_SECRET',
        'oauth_callback'        => 'PATH_TO_OAUTH_CALLBACK_URL',
    );


### TwitterComponent

in Controller append $components

    var $components = array('TwitterKit.Twitter');

callback example:

    function oauth_callback() {
    
        // check params
        if (empty($this->params['url']['oauth_token']) || empty($this->params['url']['oauth_verifier'])) {
            $this->flash(__('invalid access.', true), '/', 5);
            return;
        }
    
        // get token
        $this->Twitter->setTwitterSource('twitter');
        $token = $this->Twitter->getAccessToken();
    
        if (is_string($token)) {
            $this->flash(__('fail get access token.', true) . $token, '/', 5);
            return;
        }
    
        // create save data
        $data['User'] = array(
            'id' => $token['user_id'],
            'username' => $token['screen_name'],
            'password' => Security::hash($token['oauth_token']),
            'oauth_token' => $token['oauth_token'],
            'oauth_token_secret' => $token['oauth_token_secret'],
        );
    
        if (!$this->User->save($data)) {
            $this->flash(__('user not saved.', true), 'login', 5);
            return;
        }

        $this->Auth->login($data);

        // Redirect to Top
        $this->redirect('/');
    }

### TwitterBehavior

in Model append $actsAs

    var $actsAs = array('TwitterKit.Twitter');

add methods

 * getTwitterSource/setTwitterSource
 * twitterSaveToken
 * twitterSetToken
 * twitterSetTokenById
 * etc..

### TwitterSource

    $twitterSource = ConnectionManager::getDataSource('twitter');
    $twitterSource->setToken('ACCESS_TOKEN', 'ACCESS_TOKEN_SECRET');

### Oauth_Controller

 * /twitter_kit/oauth/authorize_url
 * /twitter_kit/oauth/authenticate_url

return authorize/authenticate url.


### Create Table 'twitter_users'

    cake/console/cake schema create TwitterKit -path app/plugins/twitter_kit/config/schema/

## License

Licensed under The MIT License.
Redistributions of files must retain the above copyright notice.


Copyright 2010, ELASTIC Consultants Inc. (http://elasticconsultants.com)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.


## Thanks

This TwitterDataSource Class use HttpSocketOAuth:

 * [Neil Crookes » OAuth extension to CakePHP HttpSocket][1] [(github)][2]
   
  [1]: http://www.neilcrookes.com/2010/04/12/cakephp-oauth-extension-to-httpsocket/
  [2]: http://github.com/neilcrookes/http_socket_oauth
