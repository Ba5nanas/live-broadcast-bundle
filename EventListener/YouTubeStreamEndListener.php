<?php
declare(strict_types=1);

/**
 * This file is part of martin1982/livebroadcastbundle which is released under MIT.
 * See https://opensource.org/licenses/MIT for full license details.
 */
namespace Martin1982\LiveBroadcastBundle\EventListener;

use Martin1982\LiveBroadcastBundle\Event\StreamEndEvent;
use Martin1982\LiveBroadcastBundle\Service\ChannelApi\YouTubeApiService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class YouTubeStreamEndListener
 */
class YouTubeStreamEndListener implements EventSubscriberInterface
{
    /**
     * @var YouTubeApiService
     */
    protected $youTubeApi;

    /**
     * YouTubeStreamEndListener constructor
     *
     * @param YouTubeApiService $youTubeApi
     */
    public function __construct(YouTubeApiService $youTubeApi)
    {
        $this->youTubeApi = $youTubeApi;
    }

    /**
     * @param StreamEndEvent $event
     *
     * @todo signal to end a stream
     */
    public function onStreamEnd(StreamEndEvent $event): void
    {
//        $broadcast = $event->getBroadcast();
//        $channel = $event->getChannel();
//
//        if ($channel instanceof ChannelYouTube) {
//            $this->youTubeApi->transitionState($broadcast, $channel, StreamEvent::STATE_REMOTE_COMPLETE);
//        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [StreamEndEvent::NAME => 'onStreamEnd'];
    }
}
