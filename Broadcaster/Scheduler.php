<?php
declare(strict_types=1);

/**
 * This file is part of martin1982/livebroadcastbundle which is released under MIT.
 * See https://opensource.org/licenses/MIT for full license details.
 */
namespace Martin1982\LiveBroadcastBundle\Broadcaster;

use Doctrine\ORM\EntityManager;
use Martin1982\LiveBroadcastBundle\Entity\Channel\AbstractChannel;
use Martin1982\LiveBroadcastBundle\Entity\LiveBroadcast;
use Martin1982\LiveBroadcastBundle\Exception\LiveBroadcastException;
use Martin1982\LiveBroadcastBundle\Service\StreamInputService;
use Martin1982\LiveBroadcastBundle\Service\StreamOutput\DynamicStreamUrlInterface;
use Martin1982\LiveBroadcastBundle\Service\StreamOutputService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Scheduler
 */
class Scheduler
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SchedulerCommandsInterface
     */
    protected $schedulerCommands;

    /**
     * @var StreamOutputService
     */
    protected $outputService;

    /**
     * @var StreamInputService
     */
    protected $inputService;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RunningBroadcast[]
     */
    protected $runningBroadcasts = [];

    /**
     * @var LiveBroadcast[]
     */
    protected $plannedBroadcasts = [];

    /**
     * Scheduler constructor
     *
     * @param EntityManager              $entityManager
     * @param SchedulerCommandsInterface $schedulerCommands
     * @param StreamOutputService        $outputService
     * @param StreamInputService         $inputService
     * @param EventDispatcherInterface   $dispatcher
     * @param LoggerInterface            $logger
     *
     * phpcs:disable Symfony.Functions.Arguments.Invalid
     */
    public function __construct(
        EntityManager $entityManager,
        SchedulerCommandsInterface $schedulerCommands,
        StreamOutputService $outputService,
        StreamInputService $inputService,
        EventDispatcherInterface $dispatcher,
        LoggerInterface $logger
    ) {
        // phpcs:enable Symfony.Functions.Arguments.Invalid
        $this->entityManager = $entityManager;
        $this->schedulerCommands = $schedulerCommands;
        $this->outputService = $outputService;
        $this->inputService = $inputService;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    /**
     * Run streams that need to be running.
     *
     * @throws LiveBroadcastException
     */
    public function applySchedule(): void
    {
        $this->stopExpiredBroadcasts();
        $this->startPlannedBroadcasts();
    }

    /**
     * Start planned broadcasts if not already running.
     *
     * @throws LiveBroadcastException
     */
    protected function startPlannedBroadcasts(): void
    {
        $this->getPlannedBroadcasts();

        foreach ($this->plannedBroadcasts as $plannedBroadcast) {
            $this->startBroadcastOnChannels($plannedBroadcast);
        }

        $this->updateRunningBroadcasts();
    }

    /**
     * @param LiveBroadcast $plannedBroadcast
     */
    protected function startBroadcastOnChannels(LiveBroadcast $plannedBroadcast): void
    {
        $channels = $plannedBroadcast->getOutputChannels();

        foreach ($channels as $channel) {
            $isBroadcasting = $this->isBroadcasting($plannedBroadcast, $channel);

            // Run broadcasts which need to start
            if (!$isBroadcasting) {
                $this->startBroadcast($plannedBroadcast, $channel);
            }
        }
    }

    /**
     * @param LiveBroadcast   $broadcast
     * @param AbstractChannel $channel
     *
     * @return bool
     */
    protected function isBroadcasting(LiveBroadcast $broadcast, AbstractChannel $channel): bool
    {
        $isBroadcasting = false;

        foreach ($this->runningBroadcasts as $runningBroadcast) {
            $sameChannel = $runningBroadcast->getChannelId() === $channel->getChannelId();
            $sameBroadcast = $runningBroadcast->getBroadcastId() === $broadcast->getBroadcastId();

            if ($sameBroadcast && $sameChannel) {
                $isBroadcasting = true;
            }
        }

        return $isBroadcasting;
    }

    /**
     * Stop running broadcasts that have expired.
     */
    protected function stopExpiredBroadcasts(): void
    {
        $this->updateRunningBroadcasts();
        $broadcastRepository = $this->entityManager->getRepository('LiveBroadcastBundle:LiveBroadcast');

        foreach ($this->runningBroadcasts as $runningBroadcast) {
            $broadcast = $broadcastRepository->find($runningBroadcast->getBroadcastId());

            if (!($broadcast instanceof LiveBroadcast)) {
                $this->logger->error(
                    'Unable to stop broadcast, PID not found in database',
                    [
                        'broadcast_id' => $runningBroadcast->getBroadcastId(),
                        'pid' => $runningBroadcast->getProcessId(),
                    ]
                );
                continue;
            }

            $isPastEndTime = $broadcast->getEndTimestamp() < new \DateTime();
            if ($isPastEndTime && $broadcast->isStopOnEndTimestamp()) {
                $this->logger->info(
                    'Stop broadcast',
                    [
                        'broadcast_id' => $broadcast->getBroadcastId(),
                        'broadcast_name' => $broadcast->getName(),
                        'pid' => $runningBroadcast->getProcessId(),
                    ]
                );
                $this->schedulerCommands->stopProcess($runningBroadcast->getProcessId());
            }
        }

        $this->updateRunningBroadcasts();
    }

    /**
     * Retrieve what is broadcasting from the process list
     *
     * @return RunningBroadcast[]
     */
    protected function updateRunningBroadcasts(): array
    {
        $this->runningBroadcasts = [];
        $this->logger->debug('Retrieve running broadcasts');
        $processStrings = $this->schedulerCommands->getRunningProcesses();

        foreach ($processStrings as $processString) {
            $runningItem = new RunningBroadcast(
                $this->schedulerCommands->getBroadcastId($processString),
                $this->schedulerCommands->getProcessId($processString),
                $this->schedulerCommands->getChannelId($processString),
                $this->schedulerCommands->getEnvironment($processString)
            );

            if ($runningItem->isValid($this->schedulerCommands->getKernelEnvironment())) {
                $this->runningBroadcasts[] = $runningItem;
            }
        }

        return $this->runningBroadcasts;
    }

    /**
     * Initiate a new broadcast.
     *
     * @param LiveBroadcast   $broadcast
     * @param AbstractChannel $channel
     */
    protected function startBroadcast(LiveBroadcast $broadcast, AbstractChannel $channel): void
    {
        try {
            $input = $this->inputService->getInputInterface($broadcast->getInput());
            $output = $this->outputService->getOutputInterface($channel);
        } catch (LiveBroadcastException $exception) {
            $this->logger->error(
                'Could not start broadcast',
                [
                    'broadcast_id' => $broadcast->getBroadcastId(),
                    'broadcast_name' => $broadcast->getName(),
                    'exception' => $exception->getMessage(),
                ]
            );

            return;
        }

        if ($output instanceof DynamicStreamUrlInterface) {
            $output->setBroadcast($broadcast);
        }

        $this->logger->info(
            'Start broadcast',
            [
                'broadcast_id' => $broadcast->getBroadcastId(),
                'broadcast_name' => $broadcast->getName(),
                'channel_id' => $channel->getChannelId(),
                'channel_name' => $channel->getChannelName(),
                'input_cmd' => $input->generateInputCmd(),
                'output_cmd' => $output->generateOutputCmd(),
            ]
        );

        $this->schedulerCommands->setIsLoopable($broadcast->isStopOnEndTimestamp());
        $this->schedulerCommands->startProcess($input->generateInputCmd(), $output->generateOutputCmd(), [
            'broadcast_id' => $broadcast->getBroadcastId(),
            'channel_id' => $channel->getChannelId(),
        ]);
    }

    /**
     * Get the planned broadcast items.
     *
     * @return LiveBroadcast[]
     *
     * @throws LiveBroadcastException
     */
    protected function getPlannedBroadcasts(): array
    {
        $broadcastRepository = $this->entityManager->getRepository('LiveBroadcastBundle:LiveBroadcast');
        $this->logger->debug('Get planned broadcasts');
        $this->plannedBroadcasts = $broadcastRepository->getPlannedBroadcasts();

        return $this->plannedBroadcasts;
    }
}
