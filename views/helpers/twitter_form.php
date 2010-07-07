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
     *      type: element type (default: textarea)
     *      maxLength:   text max length (default: 140)
     *      counterText: length message
     *      submit: submit button message. if set to false, not create.
     *      jqueryCharCount: path to charCount.js (jquery plugin)
     *      other keys set to input element options.
     */
    public function tweet($fieldName, $options = array()) {

        $this->setEntity($fieldName);
        $domId = !empty($options['id']) ? $options['id'] : $this->domId($fieldName);

        $default = array(
            'type' => 'textarea',
            'maxlength' => 140,
            'jqueryCharCount' => '/twitter_kit/js/charCount.js',
            'counterText' => __d('twitter_kit', 'Characters left: ', true),
            'submit' => __d('twitter_kit', 'Tweet', true),
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
            'loading' => __d('twitter_kit', 'Loading...', true),
            'login' => __d('twitter_kit', 'Login Twitter', true), 
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


    /**
     * linkify text
     *
     * @param string $value
     * @param array  $options
     *    username: linkify username. eg. @username
     *    hashtag : linkify hashtag. eg. #hashtag
     *    url     : linkify url. eg. http://example.com/
     * @return string
     */
    public function linkify($value, $options = array()) {

        $default = array(
            'url'      => true,
            'username' => true,
            'hashtag'  => true,
        );

        $validChars = '(?:[' . preg_quote('!"$&\'()*+,-.@_:;=~', '!') . '\/0-9a-z]|(?:%[0-9a-f]{2}))';
        $_urlMatch = 'https?://(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,6})' .
            '(?::[1-9][0-9]{0,4})?' . '(?:\/' . $validChars . '*)?' . '(?:\?' . $validChars . '*)?' . '(?:#' . $validChars . '*)?';

        $replaces = array(
            'url'      => array('!(^|[\W])(' . $_urlMatch . ')([\W]|$)!iu' => '$1<a href="$2">$2</a>$3'),
            'username' => array('!(^|[^\w/?&;])@(\w+)!iu' => '$1<a href="http://twitter.com/$2">@$2</a>$3'),
            'hashtag'  => array('!(^|[^\w/?&;])#(\w+)!iu' => '$1<a href="http://search.twitter.com/search?q=#$2">#$2</a>$3'),
        );

        $options = am($default, $options);

        foreach ($replaces as $key => $_replace) {
            if ($options[$key]) {
                $value = preg_replace(array_keys($replaces[$key]), array_values($replaces[$key]), $value);
            }
        }

        return $value;
    }

}