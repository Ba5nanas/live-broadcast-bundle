<?php

namespace Martin1982\LiveBroadcastBundle\Tests\DependencyInjection\Compiler;

use Martin1982\LiveBroadcastBundle\DependencyInjection\Compiler\AddStreamOutputPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class AddStreamOutputPassTest
 * @package Martin1982\LiveBroadcastBundle\Tests\DependencyInjection\Compiler
 */
class AddStreamOutputPassTest extends TestCase
{
    /**
     * @var AddStreamOutputPass
     */
    private $compilerPass;

    /**
     *
     */
    public function setUp()
    {
        $this->compilerPass = new AddStreamOutputPass();
    }

    /**
     * Test that no processing takes place when the service isn't named correctly
     */
    public function testNoProcessingServices()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects(static::never())
            ->method('findDefinition');

        $container->expects(static::once())
            ->method('hasDefinition')
            ->with('live.broadcast.streamoutput.service')
            ->willReturn(false);

        $this->compilerPass->process($container);
    }

    /**
     * Test processing tagged services
     */
    public function testProcessServices()
    {
        $definition = $this->createMock(Definition::class);
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects(static::once())
            ->method('findDefinition')
            ->with('live.broadcast.streamoutput.service')
            ->willReturn($definition);

        $container->expects(static::once())
            ->method('hasDefinition')
            ->with('live.broadcast.streamoutput.service')
            ->willReturn(true);

        $container->expects(static::once())
            ->method('findTaggedServiceIds')
            ->with('live.broadcast.output')
            ->willReturn([['live.broadcast.output.unittest' => ['platform' => 'Unit Test']]]);

        $definition->expects(static::once())
            ->method('addMethodCall')
            ->with('addStreamOutput', [new Reference('0'), 'Unit Test']);

        $this->compilerPass->process($container);
    }
}
