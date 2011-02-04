<?php

App::import('Helper', 'Html');
App::import('Helper', 'TwitterKit.Twitter');

class TwitterTestCase extends CakeTestCase {

    /**
     *
     * @var TwitterHelper
     */
    var $Twitter;

    function startTest() {
        $this->Twitter = new TwitterHelper();
        $this->Twitter->Html = new HtmlHelper();
        ClassRegistry::init('View', 'view');
    }

    function endTest() {
        unset($this->Twitter);
        ClassRegistry::flush();
    }

    function testLinkify() {

        $value = '@username';
        $result = '<a href="http://twitter.com/username">@username</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);
        $this->assertEqual($this->Twitter->linkify($value, array('username' => false)), $value);

        $value = '#hashtag';
        $result = '<a href="http://search.twitter.com/search?q=%23hashtag">#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);
        $this->assertEqual($this->Twitter->linkify($value, array('hashtag' => false)), $value);

        $value = 'http://example.com';
        $result = '<a href="http://example.com">http://example.com</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);
        $this->assertEqual($this->Twitter->linkify($value, array('url' => false)), $value);

        $value = '@username #hashtag';
        $result = '<a href="http://twitter.com/username">@username</a> <a href="http://search.twitter.com/search?q=%23hashtag">#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '@username#hashtag';
        $result = '<a href="http://twitter.com/username">@username</a><a href="http://search.twitter.com/search?q=%23hashtag">#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '@username http://example.com';
        $result = '<a href="http://twitter.com/username">@username</a> <a href="http://example.com">http://example.com</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = 'http://example.com #hashtag';
        $result = '<a href="http://example.com">http://example.com</a> <a href="http://search.twitter.com/search?q=%23hashtag">#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = 'http://example.com/#hashtag';
        $result = '<a href="http://example.com/#hashtag">http://example.com/#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '@user_name';
        $result = '<a href="http://twitter.com/user_name">@user_name</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '#hash_tag';
        $result = '<a href="http://search.twitter.com/search?q=%23hash_tag">#hash_tag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '@user%name';
        $result = '<a href="http://twitter.com/user">@user</a>%name';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = 'http://example.com:8080/path?query=search&order=asc#hashtag';
        $result = '<a href="http://example.com:8080/path?query=search&order=asc#hashtag">http://example.com:8080/path?query=search&order=asc#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = 'http://subdomain.example.com:8080/?query=search&order=asc#hashtag';
        $result = '<a href="http://subdomain.example.com:8080/?query=search&order=asc#hashtag">http://subdomain.example.com:8080/?query=search&order=asc#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = 'http://subdomain.example.com:8080/?#hashtag';
        $result = '<a href="http://subdomain.example.com:8080/?#hashtag">http://subdomain.example.com:8080/?#hashtag</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '@username @nameuser';
        $result = '<a href="http://twitter.com/username">@username</a> <a href="http://twitter.com/nameuser">@nameuser</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);

        $value = '#hashtag #taghash';
        $result = '<a href="http://search.twitter.com/search?q=%23hashtag">#hashtag</a> <a href="http://search.twitter.com/search?q=%23taghash">#taghash</a>';
        $this->assertEqual($this->Twitter->linkify($value), $result);
    }

    function testTweetButton() {

        $view = ClassRegistry::getObject('view');
        /* @var $view View */

        $result = $this->Twitter->tweetButton();
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=horizontal&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'default call %s');
        $this->assertTrue(in_array('<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>', $view->__scripts));

        $result = $this->Twitter->tweetButton(null);
        $this->assertEqual($result, $ok, 'null label');

        $result = $this->Twitter->tweetButton('');
        $this->assertEqual($result, $ok, 'empty label');

        $result = $this->Twitter->tweetButton(null, null);
        $this->assertEqual($result, $ok, 'empty option');

        $result = $this->Twitter->tweetButton(null, null, null);
        $this->assertEqual($result, $ok, 'null query flag');

        $result = $this->Twitter->tweetButton(null, null, null, null);
        $this->assertEqual($result, $ok, 'null inline flag');

        $result = $this->Twitter->tweetButton('TestLabel');
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
        $result = $this->Twitter->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?url=testUrl&amp;via=testVia&amp;text=testText&amp;related=testRelated&amp;count=horizontal&amp;lang=ja&amp;counturl=testCounturl" class="testClass">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $options = array(
            'count' => 'none',
        );
        $result = $this->Twitter->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=none&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $options = array(
            'count' => 'vertical',
        );
        $result = $this->Twitter->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=vertical&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $options = array(
            'count' => 'top',
        );
        $result = $this->Twitter->tweetButton(null, $options);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=none&amp;lang=en" class="twitter-share-button">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $result = $this->Twitter->tweetButton(null, null, true);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share" class="twitter-share-button" data-count="horizontal" data-lang="en">Tweet</a>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'Test Options');

        $this->startTest();
        $result = $this->Twitter->tweetButton(null, null, null, true);
        $ok = <<<OUTPUT_EOL
<a href="http://twitter.com/share?count=horizontal&amp;lang=en" class="twitter-share-button">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
OUTPUT_EOL;
        $this->assertEqual($result, $ok, 'default call');
    }

}