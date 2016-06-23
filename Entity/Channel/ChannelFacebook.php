<?php

namespace Martin1982\LiveBroadcastBundle\Entity\Channel;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ChannelFacebook.
 *
 * @ORM\Table(name="channel_facebook", options={"collate"="utf8mb4_general_ci", "charset"="utf8mb4"})
 * @ORM\Entity()
 */
class ChannelFacebook extends BaseChannel
{
    /**
     * @var string
     *
     * @ORM\Column(name="access_token", type="string", length=255, nullable=false)
     */
    protected $accessToken;

    /**
     * @var string
     *
     * @ORM\Column(name="fb_entity_id", type="string", length=128, nullable=false)
     */
    protected $fbEntityId;

    /**
     * @var string
     *
     * @ORM\Column(name="fb_app_id", type="string", length=128, nullable=false)
     */
    protected $applicationId;

    /**
     * @var string
     *
     * @ORM\Column(name="fb_app_secret", type="string", length=255, nullable=false)
     */
    protected $applicationSecret;

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     *
     * @return ChannelFacebook
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getFbEntityId()
    {
        return $this->fbEntityId;
    }

    /**
     * @param string $fbEntityId
     *
     * @return ChannelFacebook
     */
    public function setFbEntityId($fbEntityId)
    {
        $this->fbEntityId = $fbEntityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getApplicationId()
    {
        return $this->applicationId;
    }

    /**
     * @param string $applicationId
     * @return ChannelFacebook
     */
    public function setApplicationId($applicationId)
    {
        $this->applicationId = $applicationId;

        return $this;
    }

    /**
     * @return string
     */
    public function getApplicationSecret()
    {
        return $this->applicationSecret;
    }

    /**
     * @param $applicationSecret
     * @return ChannelFacebook
     */
    public function setApplicationSecret($applicationSecret)
    {
        $this->applicationSecret = $applicationSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'Facebook: '.$this->getChannelName();
    }
}
