<?php
App::import('Helper', 'Form');
/**
 * TwitteKit TwitterForm Helper
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
class TwitterFormHelper extends AppHelper {

    public $helpers = array('Html', 'Form', 'Js');

    /**
     *
     * @var HtmlHelper
     */
    public $Html;

    /**
     *
     * @var FormHelper
     */
    public $Form;

    /**
     *
     * @var JsHelper
     */
    public $Js;

    /**
     * create tweet box
     *
     * @param $fieldName
     * @param $options
     */
    public function tweet($fieldName, $options = array()) {

        $this->setEntity($fieldName);
        $domId = $this->domId($fieldName);

        $default = array(
            'type' => 'textarea',
            'maxlength' => 140,
            'jqueryCharCount' => '/twitter_kit/js/charCount.js',
            'counterText' => __('Characters left: ', true),
            'submit' => __('Tweet', true),
        );

        $options = am($default, $options);
        $inputOptions = $options;
        unset($inputOptions['jqueryCharCount']);
        unset($inputOptions['counterText']);
        unset($inputOptions['submit']);

        $out  = $this->Html->script($options['jqueryCharCount']);

        $out .= $this->Form->input($fieldName, $inputOptions);

        $out .= $this->Js->buffer("
            $('#{$domId}').charCount({
                limit: {$options['maxlength']},
                counterText: '{$options['counterText']}',
                exceeded: function(element) {
                    $('#{$domId}Submit').attr('disabled', true);
                },
                allowed: function(element) {
                    $('#{$domId}Submit').removeAttr('disabled');
                }
            });
        ");

        if ($options['submit']) {
            $out .= $this->Form->submit($options['submit'], array('id' => $domId . 'Submit'));
        }

        return $this->output($out);

    }

    /**
     * create OAuth Link
     *
     * @param $options
     *  loading:      loading message
     *  login:        login link text
     *  datasource:   datasource name (default: twitter)
     *  authenticate: use authenticate link (default: false)
     */
    public function oauthLink($options = array()) {

        $default = array(
            'loading' => __('Loading...', true),
            'login' => __('Login Twitter', true), 
            'datasource' => 'twitter',
            'authenticate' => false,
            'loginElementId' => 'twitter-login-wrap',
        );

        $options = am($default, $options);

        $action = $options['authenticate'] ? 'authenticate_url' : 'authorize_url';

        $request_url = $this->Html->url(array('plugin' => 'twitter_kit', 'controller' => 'oauth', 'action' => $action . '/' . urlencode($options['datasource'])), true);

        $this->Js->buffer("
            $.getJSON('{$request_url}', {}, function(data){
            var link = $('<a>').attr('href', data.url).text('{$options['login']}');
            $('#{$options['loginElementId']} .loading').remove();
            $('#{$options['loginElementId']}').append(link);
            });
        ");

        $out = sprintf('<span id="%s"><span class="loading">%s</span></span>', $options['loginElementId'], $options['loading']);

        return $this->output($out);
    }

}