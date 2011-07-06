<?php
/**
 * Create twitter link
 *
 * for CakePHP 2.0+
 * PHP version 5.2+
 * and use jQuery
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
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.views.elements
 * @since      TwitterKit 1.0
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/

$load_message  = isset($load_message)  ? $load_message  : __d('twitter_kit', 'Loading...');
$login_message = isset($login_message) ? $login_message : __d('twitter_kit', 'Login Twitter');
$datasource    = isset($datasource)    ? $datasource    : 'twitter';
$auth_url      = isset($authenticate) && $authenticate  ? 'authenticate_url' : 'authorize_url';
$this->Js->buffer("
$.getJSON('{$this->Html->url('/twitter_kit/oauth/' . $auth_url . '/' . $datasource, true)}', {}, function(data){
    var link = $('<a>').attr('href', data.url).text('{$login_message}');
    $('#twitter-login-wrap .loading').remove();
    $('#twitter-login-wrap').append(link);
});
");
?>
<span id="twitter-login-wrap">
<span class="loading"><?php echo $load_message; ?></span>
</span>