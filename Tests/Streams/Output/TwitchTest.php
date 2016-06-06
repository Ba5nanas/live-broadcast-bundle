<?php

namespace Martin1982\LiveBroadcastBundle\Tests\Streams\Output;

use Martin1982\LiveBroadcastBundle\Entity\Channel\ChannelTwitch;
use Martin1982\LiveBroadcastBundle\Streams\Output\Twitch;

/**
 * Class TwitchTest
 * @package Martin1982\LiveBroadcastBundle\Tests\Streams\Output
 */
class TwitchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChannelTwitch
     */
    private $twitchChannel;

    /**
     * Setup a testable Twitch channel
     */
    public function setUp()
    {
        $this->twitchChannel = new ChannelTwitch();
        $this->twitchChannel->setStreamServer('value1');
        $this->twitchChannel->setStreamKey('value2');
    }

    /**
     * Test if the Twitch output class implements the correct interface.
     */
    public function testTwitchContstructor()
    {
        $twitchOutput = new Twitch($this->twitchChannel);
        self::assertInstanceOf('Martin1982\LiveBroadcastBundle\Streams\Output\Twitch', $twitchOutput);
    }

    /**
     * Test if the Twitch output class generates the correct output command.
     */
    public function testGenerateOutputCmd()
    {
        $twitchOutput = new Twitch($this->twitchChannel);
        self::assertEquals($twitchOutput->generateOutputCmd(), '-f flv rtmp://value1/app/value2');
    }
}
