<?php

namespace Martin1982\LiveBroadcastBundle\Broadcaster;

/**
 * Interface SchedulerCommandsInterface.
 */
interface SchedulerCommandsInterface
{
    /**
     * @param string $input
     * @param string $output
     * @param array  $metadata
     *
     * @return string
     */
    public function startProcess($input, $output, $metadata);

    /**
     * @param int $pid
     *
     * @return string
     */
    public function stopProcess($pid);

    /**
     * @return array
     */
    public function getRunningProcesses();

    /**
     * @param string $processString
     *
     * @return int|null
     */
    public function getProcessId($processString);

    /**
     * @param string $processString
     *
     * @return int|null
     */
    public function getBroadcastId($processString);

    /**
     * @param string $processString
     *
     * @return int|null
     */
    public function getChannelId($processString);
}
