<?php

namespace Martin1982\LiveBroadcastBundle\Admin;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Martin1982\LiveBroadcastBundle\Entity\Channel\ChannelYouTube;
use Martin1982\LiveBroadcastBundle\Entity\LiveBroadcast;
use Martin1982\LiveBroadcastBundle\Service\YouTubeApiService;
use Psr\Log\LoggerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Class LiveBroadcastAdmin.
 */
class LiveBroadcastAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'broadcast';

    /**
     * @var array
     */
    protected $datagridValues = array(
        '_page' => 1,
        '_sort_order' => 'DESC',
        '_sort_by' => 'startTimestamp',
    );

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $fileFieldOptions = ['required' => false, 'label' => 'Thumbnail (min. 1280x720px, 16:9 ratio)'];

        /** @var LiveBroadcast $broadcast */
        $broadcast = $this->getSubject();

        if ($broadcast->getThumbnail()) {
            $container = $this->getConfigurationPool()->getContainer();

            $fullPath = sprintf(
                '%s/%s',
                $container->getParameter('livebroadcast.thumbnail.web_path'),
                $broadcast->getThumbnail()->getFilename()
            );

            $fileFieldOptions['help'] = '<img src="'.$fullPath.'" height="650" width="1200"/>';
        }

        $formMapper
            ->with('General', array(
                'class' => 'col-md-8',
            ))
            ->add('name', 'text', array('label' => 'Name'))
            ->add('description', 'textarea', array(
                'label' => 'Description',
                'required' => false,
                'attr' => array('class' => 'form-control', 'rows' => 5),
            ))
            ->add('thumbnail', 'file', $fileFieldOptions)
            ->add('startTimestamp', 'sonata_type_datetime_picker', array(
                'label' => 'Broadcast start',
                'dp_side_by_side' => true,
            ))
            ->add('endTimestamp', 'sonata_type_datetime_picker', array(
                'label' => 'Broadcast end',
                'dp_side_by_side' => true,
            ))
            ->add('stopOnEndTimestamp', 'checkbox', array(
                'label' => 'Stop on broadcast end timestamp',
                'required' => false,
            ))
            ->end()
            ->with('Video Input', array(
                'class' => 'col-md-4',
            ))
            ->add('input', 'sonata_type_model_list', array('btn_list' => false))
            ->end()
            ->with('Channels', array(
                'class' => 'col-md-4',
            ))
            ->add('outputChannels', 'sonata_type_model', array(
                'multiple' => true,
                'expanded' => true,
            ))
            ->end();
    }

    /**
     * @param LiveBroadcast $broadcast
     */
    public function postPersist($broadcast)
    {
        $this->loadThumbnail($broadcast);

        foreach ($broadcast->getOutputChannels() as $channel) {
            if ($channel instanceof ChannelYouTube) {
                $youTubeService = $this->getYouTubeService();
                $youTubeService->createLiveEvent($broadcast, $channel);
            }
        }

        parent::postPersist($broadcast);
    }

    /**
     * @param LiveBroadcast $broadcast
     */
    public function postUpdate($broadcast)
    {
        foreach ($broadcast->getOutputChannels() as $channel) {
            if ($channel instanceof ChannelYouTube) {
                $youTubeService = $this->getYouTubeService();
                $youTubeService->updateLiveEvent($broadcast, $channel);
            }
        }

        parent::postUpdate($broadcast);
    }

    /**
     * @param LiveBroadcast $broadcast
     */
    public function preRemove($broadcast)
    {
        foreach ($broadcast->getOutputChannels() as $channel) {
            if ($channel instanceof ChannelYouTube) {
                $youTubeService = $this->getYouTubeService();
                try {
                    $youTubeService->removeLiveEvent($broadcast, $channel);
                } catch (\Google_Service_Exception $ex) {
                    /** @var LoggerInterface $logger */
                    $logger = $this->getConfigurationPool()->getContainer()->get('logger');
                    $logger->warning($ex->getMessage());
                }
            }
        }

        parent::preRemove($broadcast);
    }

    /**
     * Get the YouTube Live service
     *
     * @return YouTubeApiService
     * @throws \Exception
     */
    protected function getYouTubeService()
    {
        $youTubeService = $this->getConfigurationPool()->getContainer()->get('live.broadcast.youtubeapi.service');
        $redirectService = $this->getConfigurationPool()->getContainer()->get('live.broadcast.googleredirect.service');

        $youTubeService->initApiClients($redirectService->getOAuthRedirectUrl());

        return $youTubeService;
    }

    /**
     * @param LiveBroadcast $liveBroadcast
     */
    protected function loadThumbnail(LiveBroadcast $liveBroadcast)
    {
        $uploadListener = $this->getConfigurationPool()->getContainer()->get('live.broadcast.thumbnail.listener');
        $objectManager = $this->getConfigurationPool()->getContainer()->get('Doctrine')->getManager();
        $lifeCycleEvent = new LifecycleEventArgs($liveBroadcast, $objectManager);
        $uploadListener->postLoad($lifeCycleEvent);
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('startTimestamp')
            ->add('endTimestamp');
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name')
            ->add('outputChannels', 'sonata_type_model', array('label' => 'Channel(s)'))
            ->add('startTimestamp', 'datetime', array('label' => 'Start time'))
            ->add('endTimestamp', 'datetime', array('label' => 'End time'))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                ),
            ))
        ;
    }
}
