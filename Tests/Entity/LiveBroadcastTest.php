<?php

namespace Martin1982\LiveBroadcastBundle\Tests\Entity;

use Martin1982\LiveBroadcastBundle\Entity\Channel\BaseChannel;
use Martin1982\LiveBroadcastBundle\Entity\LiveBroadcast;
use Martin1982\LiveBroadcastBundle\Entity\Media\BaseMedia;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class LiveBroadcastTest
 * @package Martin1982\LiveBroadcastBundle\Tests\Entity
 */
class LiveBroadcastTest extends TestCase
{
    /**
     * Test class instance.
     */
    public function testClass()
    {
        $broadcast = new LiveBroadcast();
        self::assertInstanceOf(LiveBroadcast::class, $broadcast);
    }

    /**
     * Test get methods.
     */
    public function testGetMethods()
    {
        $now = new \DateTime();
        $endTime = new \DateTime('+1 hour');

        $channelOne = $this->createMock(BaseChannel::class);
        $channelTwo = $this->createMock(BaseChannel::class);

        $baseChannels = [
            $channelOne,
            $channelTwo,
        ];

        $broadcast = new LiveBroadcast();
        $broadcast->setName('Test');
        $broadcast->setDescription('Description of broadcast');
        $broadcast->setThumbnail(new File('test', false));
        $broadcast->setOutputChannels($baseChannels);
        $broadcast->setInput($this->createMock(BaseMedia::class));

        self::assertEquals('-', (string) $broadcast);
        self::assertNull($broadcast->getBroadcastId());
        self::assertEquals($now->format('Y-m-d H:i:s'), $broadcast->getStartTimestamp()->format('Y-m-d H:i:s'));
        self::assertEquals($endTime->format('Y-m-d H:i:s'), $broadcast->getEndTimestamp()->format('Y-m-d H:i:s'));
        self::assertCount(2, $broadcast->getOutputChannels());
        self::assertEquals('Test', $broadcast->getName());
        self::assertEquals('Description of broadcast', $broadcast->getDescription());
        self::assertInstanceOf(File::class, $broadcast->getThumbnail());
        self::assertInstanceOf(BaseChannel::class, $broadcast->getOutputChannels()[0]);
        $broadcast->removeOutputChannel($channelTwo);
        self::assertCount(1, $broadcast->getOutputChannels());
        self::assertInstanceOf(BaseMedia::class, $broadcast->getInput());

        /* Check default value */
        self::assertTrue($broadcast->isStopOnEndTimestamp());
        $broadcast->setStopOnEndTimestamp(false);
        self::assertFalse($broadcast->isStopOnEndTimestamp());
    }
}
