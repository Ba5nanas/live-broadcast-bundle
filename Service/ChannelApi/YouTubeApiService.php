<?php
declare(strict_types=1);

/**
 * This file is part of martin1982/livebroadcastbundle which is released under MIT.
 * See https://opensource.org/licenses/MIT for full license details.
 */
namespace Martin1982\LiveBroadcastBundle\Service\ChannelApi;

use Doctrine\ORM\EntityManager;
use Martin1982\LiveBroadcastBundle\Entity\Channel\AbstractChannel;
use Martin1982\LiveBroadcastBundle\Entity\Channel\ChannelYouTube;
use Martin1982\LiveBroadcastBundle\Entity\Channel\PlanableChannelInterface;
use Martin1982\LiveBroadcastBundle\Entity\LiveBroadcast;
use Martin1982\LiveBroadcastBundle\Entity\Metadata\StreamEvent;
use Martin1982\LiveBroadcastBundle\Entity\Metadata\StreamEventRepository;
use Martin1982\LiveBroadcastBundle\Exception\LiveBroadcastOutputException;
use Martin1982\LiveBroadcastBundle\Service\ChannelApi\Client\YouTubeClient;
use Psr\Log\LoggerInterface;

/**
 * Class YouTubeApiService
 */
class YouTubeApiService implements ChannelApiInterface
{
    /**
     * @var string
     */
    protected $host;

    /**
     * @var string
     */
    protected $thumbnailDir;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Google_Client
     */
    protected $googleApiClient;

    /**
     * @var \Google_Service_YouTube
     */
    protected $youTubeApiClient;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var YouTubeClient
     */
    protected $client;

    /**
     * YouTubeApiService constructor
     *
     * @param EntityManager   $entityManager
     * @param LoggerInterface $logger
     * @param YouTubeClient   $youTubeClient
     */
    public function __construct(EntityManager $entityManager, LoggerInterface $logger, YouTubeClient $youTubeClient)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->client = $youTubeClient;
    }

    /**
     * @param LiveBroadcast   $broadcast
     * @param AbstractChannel $channel
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws LiveBroadcastOutputException
     */
    public function createLiveEvent(LiveBroadcast $broadcast, AbstractChannel $channel): void
    {
        $this->setChannel($channel);

        $youtubeBroadcast = $this->client->createBroadcast($broadcast);
        $stream           = $this->client->createStream($broadcast->getName());
        $youtubeBroadcast = $this->client->bind($youtubeBroadcast, $stream);

        $streamEvent = new StreamEvent();
        $streamEvent->setBroadcast($broadcast);
        $streamEvent->setChannel($channel);
        $streamEvent->setExternalStreamId($youtubeBroadcast->getId());

        $this->entityManager->persist($streamEvent);
        $this->entityManager->flush();
    }

    /**
     * @param LiveBroadcast   $broadcast
     * @param AbstractChannel $channel
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\ORMException
     * @throws LiveBroadcastOutputException
     */
    public function removeLiveEvent(LiveBroadcast $broadcast, AbstractChannel $channel): void
    {
        $this->setChannel($channel);

        $eventRepository = $this->getEventRepository();
        $streamEvent = $eventRepository->findBroadcastingToChannel($broadcast, $channel);

        if ($streamEvent) {
            $this->client->removeLivestream($streamEvent);
            $this->entityManager->remove($streamEvent);
            $this->entityManager->flush();
        }
    }

    /**
     * @param LiveBroadcast   $broadcast
     * @param AbstractChannel $channel
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     * @throws LiveBroadcastOutputException
     */
    public function updateLiveEvent(LiveBroadcast $broadcast, AbstractChannel $channel): void
    {
        $this->setChannel($channel);

        $eventRepository = $this->getEventRepository();
        $streamEvent = $eventRepository->findBroadcastingToChannel($broadcast, $channel);

        if (!$streamEvent) {
            $this->createLiveEvent($broadcast, $channel);

            return;
        }

        $this->client->updateLiveStream($streamEvent);
    }

    /**
     * @param LiveBroadcast   $broadcast
     * @param AbstractChannel $channel
     *
     * @return string|null
     *
     * @throws LiveBroadcastOutputException
     */
    public function getStreamUrl(LiveBroadcast $broadcast, AbstractChannel $channel): ?string
    {
        $streamUrl = null;
        $this->setChannel($channel);

        $eventRepository = $this->entityManager->getRepository(StreamEvent::class);
        $event = $eventRepository->findBroadcastingToChannel($broadcast, $channel);
        if (!$event) {
            throw new LiveBroadcastOutputException('No event found');
        }
        $streamId = (string) $event->getExternalStreamId();

        $broadcast = $this->client->getYoutubeBroadcast($streamId);
        if ($broadcast) {
            $streamId  = $broadcast->getContentDetails()->getBoundStreamId();
            $streamUrl = $this->client->getStreamUrl($streamId);
        }

        return $streamUrl;
    }

    /**
     * @param PlanableChannelInterface $channel
     * @param string|int               $externalId
     *
     * @throws LiveBroadcastOutputException
     */
    public function sendEndSignal(PlanableChannelInterface $channel, $externalId): void
    {
        if (!$channel instanceof AbstractChannel) {
            return;
        }

        $this->setChannel($channel);
        $this->client->endLiveStream($externalId);
    }

    /**
     * @param AbstractChannel $channel
     *
     * @throws LiveBroadcastOutputException
     */
    private function setChannel(AbstractChannel $channel): void
    {
        if (!$channel instanceof ChannelYouTube) {
            throw new LiveBroadcastOutputException(sprintf('Expected youtube channel, got %s', \get_class($channel)));
        }

        $this->client->setChannel($channel);
    }

    /**
     * Get the YouTube Event repository
     *
     * @return StreamEventRepository
     */
    private function getEventRepository(): StreamEventRepository
    {
        return $this->entityManager->getRepository(StreamEvent::class);
    }
}
