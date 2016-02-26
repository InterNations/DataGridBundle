<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Tests\Grid;

use InterNations\Component\Testing\AbstractTestCase;
use Sorien\DataGridBundle\Grid\Column\TextColumn;
use Sorien\DataGridBundle\Grid\Grid;
use Sorien\DataGridBundle\Grid\Source\Source;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class GridTest extends AbstractTestCase
{
    /** @var Grid */
    private $grid;

    /** @var ContainerInterface|MockObject */
    private $container;

    /** @var SecurityContextInterface|MockObject */
    private $securityContext;

    /** @var SessionInterface|MockObject */
    private $session;

    /** @var Request */
    private $request;

    /** @var Source|MockObject */
    private $source;

    public function setUp()
    {
        $this->securityContext = $this->getSimpleMock(SecurityContextInterface::class);

        $this->session = $this->getSimpleMock(SessionInterface::class);

        $this->request = new Request();
        $this->request->setSession($this->session);

        $this->container = $this->getSimpleMock(ContainerInterface::class);
        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['request', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->request],
                    ['security.context', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->securityContext],
                ]
            );

        $this->source = $this->getSimpleMock(Source::class);

        $this->grid = new Grid($this->container, $this->source);
    }

    public function testAddColumn()
    {
        $this->assertCount(0, $this->grid->getColumns());
        $this->grid->addColumn(new TextColumn());
        $this->assertCount(1, $this->grid->getColumns());
    }
}
