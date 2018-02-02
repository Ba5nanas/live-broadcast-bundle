<?php

namespace Martin1982\LiveBroadcastBundle\Tests\Broadcaster\Darwin;

use Martin1982\LiveBroadcastBundle\Broadcaster\Darwin\SchedulerCommands;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

/**
 * Class SchedulerCommandsDarwinTest
 * @package Martin1982\LiveBroadcastBundle\Tests\Broadcaster\Darwin
 */
class SchedulerCommandsDarwinTest extends TestCase
{
    use PHPMock;

    /**
     * Test the running processes command.
     */
    public function testGetRunningProcesses()
    {
        $command = new SchedulerCommands('/some/directory', 'unittest');

        $exec = $this->getFunctionMock('Martin1982\LiveBroadcastBundle\Broadcaster\Darwin', 'exec');
        $exec->expects($this->once())->willReturnCallback(
            function ($command, &$output) {
                self::assertEquals('ps -ww -o pid=,args= | grep ffmpeg | grep -v grep', $command);
                // @codingStandardsIgnoreLine
                $output = '1234 ffmpeg -re -i /path/to/video.mp4 -vcodec copy -acodec copy -f flv rtmp://live-ams.twitch.tv/app/ -metadata env=unittest -metadata broadcast_id=1337';
            }
        );

        $running = $command->getRunningProcesses();
        // @codingStandardsIgnoreLine
        self::assertEquals('1234 ffmpeg -re -i /path/to/video.mp4 -vcodec copy -acodec copy -f flv rtmp://live-ams.twitch.tv/app/ -metadata env=unittest -metadata broadcast_id=1337', $running);
    }
}
