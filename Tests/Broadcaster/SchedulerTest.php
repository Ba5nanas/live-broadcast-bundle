<?php
declare(strict_types=1);

/**
 * This file is part of martin1982/livebroadcastbundle which is released under MIT.
 * See https://opensource.org/licenses/MIT for full license details.
 */
namespace Martin1982\LiveBroadcastBundle\Tests\Broadcaster;

use Doctrine\ORM\EntityManager;
use Martin1982\LiveBroadcastBundle\Broadcaster\Scheduler;
use Martin1982\LiveBroadcastBundle\Broadcaster\SchedulerCommandsInterface;
use Martin1982\LiveBroadcastBundle\Entity\LiveBroadcastRepository;
use Martin1982\LiveBroadcastBundle\Service\StreamInputService;
use Martin1982\LiveBroadcastBundle\Service\StreamOutputService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class SchedulerTest
 */
class SchedulerTest extends TestCase
{
    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var SchedulerCommandsInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $schedulerCommands;

    /**
     * @var StreamOutputService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $outputService;

    /**
     * @var StreamInputService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inputService;

    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * Test applying a schedule
     */
    public function testApplySchedule(): void
    {
        $broadcastRepository = $this->createMock(LiveBroadcastRepository::class);
        $broadcastRepository->expects(static::any())
            ->method('getPlannedBroadcasts')
            ->willReturn([]);

        $this->entityManager->expects(static::any())
            ->method('getRepository')
            ->willReturn($broadcastRepository);

        $this->dispatcher->expects(static::any())
            ->method('dispatch')
            ->willReturn(true);

        $this->logger->expects(static::any())
            ->method('error')
            ->willReturn(true);
        $this->logger->expects(static::any())
            ->method('info')
            ->willReturn(true);
        $this->logger->expects(static::any())
            ->method('debug')
            ->willReturn(true);

        $this->schedulerCommands->expects(static::any())
            ->method('getRunningProcesses')
            ->willReturn([]);

        $scheduler = new Scheduler(
            $this->entityManager,
            $this->schedulerCommands,
            $this->outputService,
            $this->inputService,
            $this->dispatcher,
            $this->logger
        );

        $scheduler->applySchedule();
        $this->addToAssertionCount(1);
    }

    /**
     * Setup default mocks
     */
    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->schedulerCommands = $this->createMock(SchedulerCommandsInterface::class);
        $this->outputService = $this->createMock(StreamOutputService::class);
        $this->inputService = $this->createMock(StreamInputService::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }
}
