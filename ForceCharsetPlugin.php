<?php

require __DIR__ . '/vendor/autoload.php';

use Guzzle\Common\Event;
use Guzzle\Http\Message\Header;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
class ForceCharsetPlugin implements EventSubscriberInterface
{
    private $forcedCharset = 'utf8';
    public static function getSubscribedEvents()
    {
        return array(
            'request.complete' => 'onRequestComplete'
        );
    }
    public function setForcedCharset($charset)
    {
        $this->forcedCharset = $charset;
        return $this;
    }
    public function getForcedCharset()
    {
        return $this->forcedCharset;
    }
    public function onRequestComplete(Event $event)
    {
        $response = $event['response'];
        $header = $response->getHeader('content-type');
        $modified = new Header(
            $header->getName(),
            array_map(array($this, 'modifyParams'), $header->parseParams()),
            $header->getGlue()
        );
        $response->setHeader('content-type', $modified);
    }
    private function modifyParams(array $headerParams)
    {
        $headerParams['charset'] = $this->getForcedCharset();
        return urldecode(http_build_query($headerParams, null, ';'));
    }
}
