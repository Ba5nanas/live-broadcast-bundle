<?php

namespace Martin1982\LiveBroadcastBundle\Service;

use Doctrine\ORM\EntityManager;
use Martin1982\LiveBroadcastBundle\Entity\LiveBroadcast;

/**
 * Class BroadcastManager
 */
class BroadcastManager
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var StreamManager
     */
    protected $streamManager;

    /**
     * BroadcastManager constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, StreamManager $streamManager)
    {
        $this->entityManager = $entityManager;
        $this->streamManager = $streamManager;
    }

    /**
     * Get a broadcast by it's id
     *
     * @param string $broadcastId
     *
     * @return LiveBroadcast|null|object
     */
    public function getBroadcastByid($broadcastId)
    {
        $broadcastRepository = $this->entityManager->getRepository(LiveBroadcast::class);

        return $broadcastRepository->findOneBy([ 'broadcastId' => (int) $broadcastId ]);
    }

    /**
     * End a broadcast on all channels
     *
     * @param LiveBroadcast $broadcast
     *
     * @throws \Martin1982\LiveBroadcastBundle\Exception\LiveBroadcastException
     */
    public function handleBroadcastEnd(LiveBroadcast $broadcast)
    {
        $channels = $broadcast->getOutputChannels();

        foreach ($channels as $channel) {
            $this->streamManager->endStream($broadcast, $channel);
        }
    }
}
