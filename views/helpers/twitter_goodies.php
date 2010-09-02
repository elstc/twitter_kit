<?php

App::import('Helper', 'Html');

/**
 * TwitteKit Twitter Goodies Helper
 *
 * Copyright 2010, ELASTIC Consultants Inc. http://elasticconsultants.com/
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @version    1.0
 * @author     nojimage <nojima at elasticconsultants.com>
 * @copyright  2010 ELASTIC Consultants Inc.
 * @license    http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link       http://elasticconsultants.com
 * @package    twitter_kit
 * @subpackage twitter_kit.views.helpers
 * @since      File available since Release 1.0
 * @modifiedby nojimage <nojima at elasticconsultants.com>
 */
class TwitterGoodiesHelper extends AppHelper {

    public $helpers = array('Html');
    /**
     *
     * @var HtmlHelper
     */
    public $Html;

    /**
     * create tweet button
     *
     * @see http://dev.twitter.com/pages/tweet_button
     * @param string  $label
     * @param array   $options
     * @param boolean $dataAttribute
     * @param boolean $scriptInline
     * @return string
     */
    public function tweetButton($label = null, $options = array(), $dataAttribute = false, $scriptInline = false) {

        $attributes = array();

        $defaults = array(
            'class' => 'twitter-share-button',
            'url' => '',
            'via' => '',
            'text' => '',
            'related' => '',
            'count' => 'horizontal', // 'none', 'vertical'
            'lang' => 'en',
            'counturl' => '',
        );

        if (empty($label)) {
            $label = 'Tweet';
        }

        $options = am($defaults, $options);

        $attributes['class'] = $options['class'];
        unset($options['class']);

        $options['count'] = strtolower($options['count']);
        if (!in_array($options['count'], array('none', 'horizontal', 'vertical'))) {
            $options['count'] = 'none';
        }

        $options = Set::filter($options);

        if ($dataAttribute) {
            foreach ($options as $key => $val) {
                $attributes['data-' . $key] = $val;
            }
            $options = array();
        }

        $out = $this->Html->link($label, 'http://twitter.com/share' . Router::queryString($options), $attributes);
        $out .= $this->Html->script('http://platform.twitter.com/widgets.js', array('inline' => $scriptInline));
        return $this->output($out);
    }

}