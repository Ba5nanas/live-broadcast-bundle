<?php
declare(strict_types=1);

/**
 * This file is part of martin1982/livebroadcastbundle which is released under MIT.
 * See https://opensource.org/licenses/MIT for full license details.
 */
namespace Martin1982\LiveBroadcastBundle\Entity\Media;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class MediaRtmp
 *
 * @ORM\Table(name="broadcast_input_rtmp", options={"collate"="utf8mb4_general_ci", "charset"="utf8mb4"})
 * @ORM\Entity()
 */
class MediaRtmp extends AbstractMedia
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="rtmp_address", type="string", nullable=false)
     *
     * @Assert\Url()
     */
    protected $rtmpAddress;

    /**
     * @return string|null
     */
    public function getRtmpAddress(): ?string
    {
        return $this->rtmpAddress;
    }

    /**
     * @param string $rtmpAddress
     *
     * @return MediaRtmp
     */
    public function setRtmpAddress($rtmpAddress): MediaRtmp
    {
        $this->rtmpAddress = str_replace('rtmp://', '', $rtmpAddress);

        return $this;
    }

    /**
     * Get input string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getRtmpAddress();
    }
}
