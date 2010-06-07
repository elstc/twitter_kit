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
            $('#${domId}').charCount({
                limit: ${options['maxlength']},
                counterText: '${options['counterText']}',
                exceeded: function(element) {
                    $('#${domId}Submit').attr('disabled', true);
                },
                allowed: function(element) {
                    $('#${domId}Submit').removeAttr('disabled');
                }
            });
        ");

        if ($options['submit']) {
            $out .= $this->Form->submit($options['submit'], array('id' => $domId . 'Submit'));
        }

        return $out;

}

}