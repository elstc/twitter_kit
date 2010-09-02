<?php

App::import('Helper', 'TwitterKit.TwitterGoodies');

class TwitterGoodiesTestCase extends CakeTestCase {

    /**
     *
     * @var TwitterGoodies
     */
    var $TwitterGoodies;

    function startTest() {
        $this->TwitterGoodies = new TwitterGoodiesHelper();
        $this->TwitterGoodies->Html = new HtmlHelper();
        ClassRegistry::init('View', 'view');
    }

    function endTest() {
        unset($this->TwitterGoodies);
        ClassRegistry::flush();
    }

    function testTweetButton() {

        $view = ClassRegistry::getObject('view');
        /* @var $view View */

        $result = $this->TwitterGoodies->tweetButton();
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=horizontal&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'default call %s');
        $this->assertTrue(in_array('<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>', $view->__scripts));

        $result = $this->TwitterGoodies->tweetButton(null);
        $this->assertEqual($result, $ok, 'null label');

        $result = $this->TwitterGoodies->tweetButton('');
        $this->assertEqual($result, $ok, 'empty label');

        $result = $this->TwitterGoodies->tweetButton(null, null);
        $this->assertEqual($result, $ok, 'empty option');

        $result = $this->TwitterGoodies->tweetButton(null, null, null);
        $this->assertEqual($result, $ok, 'null query flag');

        $result = $this->TwitterGoodies->tweetButton(null, null, null, null);
        $this->assertEqual($result, $ok, 'null inline flag');

        $result = $this->TwitterGoodies->tweetButton('TestLabel');
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=horizontal&amp;lang=en" class="twitter-share-button">TestLabel</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test label');

        $options = array(
            'class' => 'testClass',
            'url' => 'testUrl',
            'via' => 'testVia',
            'text' => 'testText',
            'related' => 'testRelated',
            'lang' => 'ja',
            'counturl' => 'testCounturl',
        );
        $result = $this->TwitterGoodies->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?url=testUrl&amp;via=testVia&amp;text=testText&amp;related=testRelated&amp;count=horizontal&amp;lang=ja&amp;counturl=testCounturl" class="testClass">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $options = array(
            'count' => 'none',
        );
        $result = $this->TwitterGoodies->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=none&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $options = array(
            'count' => 'vertical',
        );
        $result = $this->TwitterGoodies->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=vertical&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $options = array(
            'count' => 'top',
        );
        $result = $this->TwitterGoodies->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=none&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $result = $this->TwitterGoodies->tweetButton(null, null, true);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share" class="twitter-share-button" data-count="horizontal" data-lang="en">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $this->startTest();
        $result = $this->TwitterGoodies->tweetButton(null, null, null, true);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=horizontal&amp;lang=en" class="twitter-share-button">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'default call');
    }

}