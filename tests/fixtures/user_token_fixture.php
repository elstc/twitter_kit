<?php
class UserTokenFixture extends CakeTestFixture {

    var $name = 'UserToken';

    var $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
        'username' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'oauth_token' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'oauth_token_secret' => array('type' => 'text', 'null' => true, 'default' => NULL),
        'access_token' => array('type' => 'text', 'null' => true, 'default' => NULL),
        'access_token_secret' => array('type' => 'text', 'null' => true, 'default' => NULL),
    );

    var $records = array(
    array(
		'id'                  => 1,
		'username'            => 'aaa',
		'oauth_token'         => 'oauth_token1',
        'oauth_token_secret'  => 'oauth_token_secret1',
        'access_token'        => 'access_token1',
        'access_token_secret' => 'access_token_secret1',
    ),
    array(
        'id'                  => 2,
        'username'            => 'bbb',
        'oauth_token'         => 'oauth_token2',
        'oauth_token_secret'  => 'oauth_token_secret2',
        'access_token'        => 'access_token2',
        'access_token_secret' => 'access_token_secret2',
    ),
    array(
        'id'                  => 3,
        'username'            => 'ccc',
        'oauth_token'         => null,
        'oauth_token_secret'  => null,
        'access_token'        => null,
        'access_token_secret' => null,
    ),
    );
}
