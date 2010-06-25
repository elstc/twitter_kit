<?php
/**
 * TwitterKit users/login view
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
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.views.users
 * @since      TwitterKit 1.0
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/
$this->set('title_for_layout', __d('twitter_kit', 'Login', true));
?>
<?php if (!$session->check('Auth.User')) : /* 未ログインの場合 */ ?>
<?php echo $this->TwitterForm->oauthLink($linkOptions); ?>
<?php else: ?>
<div id="logout-wrap">
<p><?php echo $html->link(__d('twitter_kit', 'Logout', true), '/users/logout')?></p>
</div>
<?php endif ; ?>

