<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Tests\Grid;

use InterNations\DataGridBundle\Grid\Column\TextColumn;
use InterNations\DataGridBundle\Grid\Columns;
use InterNations\DataGridBundle\Grid\Grid;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GridTest extends TestCase
{
    /** @var ContainerInterface|MockObject */
    private $container;

    /** @var SessionInterface|MockObject */
    private $session;

    /** @var Request */
    private $request;

    /** @var UrlGeneratorInterface|MockObject */
    private $urlGenerator;

    /** @var string */
    private $route = 'grid_route';

    public function setUp()
    {
        $this->session = $this->createMock(SessionInterface::class);

        $this->request = new Request();
        $this->request->attributes->set('_route', $this->route);
        $this->request->setSession($this->session);
        $requestStack = $this->createConfiguredMock(RequestStack::class, ['getMasterRequest' => $this->request]);

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->method('get')
            ->willReturnMap(
                [
                    ['request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $requestStack],
                    ['router', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->urlGenerator]
                ]
            );
    }

    public function testAddColumn(): void
    {
        $grid = new Grid($this->container);

        $this->assertCount(0, $grid->getColumns());
        $grid->addColumn(new TextColumn());
        $this->assertCount(1, $grid->getColumns());
    }

    public function testAddColumnDefaultIsNull(): void
    {
        $columns = $this->createMock(Columns::class);
        $grid = new Grid($this->container, null, $columns);

        $column = new TextColumn();

        $columns
            ->expects($this->once())
            ->method('addColumn')
            ->with($column, null);

        $grid->addColumn($column);
    }
}
