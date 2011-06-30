<?php
/**
 * TwitterKit users/login view
 *
 * for CakePHP 1.3+
 * PHP version 5.2+
 *
 * Copyright 2011, ELASTIC Consultants Inc. (http://elasticconsultants.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @version    1.1
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2011, ELASTIC Consultants Inc.
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.views.users
 * @since      TwitterKit 1.0
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/
$this->set('title_for_layout', __d('twitter_kit', 'Login'));
?>
<?php if (!$this->Session->check('Auth.User')) : /* 未ログインの場合 */ ?>
<?php echo $this->Twitter->oauthLink($linkOptions); ?>
<?php else: ?>
<div id="logout-wrap">
<p><?php echo $this->Html->link(__d('twitter_kit', 'Logout'), '/users/logout')?></p>
</div>
<?php endif ; ?>
<?php echo $this->Twitter->Js->writeBuffer(); ?>
