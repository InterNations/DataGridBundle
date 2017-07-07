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
use InterNations\Component\Testing\AccessTrait;
use Sorien\DataGridBundle\Grid\Column\TextColumn;
use Sorien\DataGridBundle\Grid\Columns;
use Sorien\DataGridBundle\Grid\Grid;
use Sorien\DataGridBundle\Grid\Source\Source;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class GridTest extends AbstractTestCase
{
    use AccessTrait;

    /** @var Grid */
    private $grid;

    /** @var ContainerInterface|MockObject */
    private $container;

    /** @var SessionInterface|MockObject */
    private $session;

    /** @var Request */
    private $request;

    /** @var Source|MockObject */
    private $source;

    public function setUp()
    {
        $this->session = $this->createMock(SessionInterface::class);

        $this->request = new Request();
        $this->request->setSession($this->session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->request);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container
            ->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['request_stack', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $requestStack],
                ]
            );

        $this->source = $this->createMock(Source::class);

        $this->grid = new Grid($this->container, $this->source);
    }

    public function testAddColumn()
    {
        $this->assertCount(0, $this->grid->getColumns());
        $this->grid->addColumn(new TextColumn());
        $this->assertCount(1, $this->grid->getColumns());
    }

    public function testAddColumnDefaultIsNull()
    {
        $columns = $this->createMock(Columns::class);
        $this->setNonPublicProperty($this->grid, 'columns', $columns);

        $column = new TextColumn();

        $columns
            ->expects($this->once())
            ->method('addColumn')
            ->with($column, $this->identicalTo(null));

        $this->grid->addColumn($column);
    }
}
