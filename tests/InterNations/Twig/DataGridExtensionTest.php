<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Tests\Twig;

use PHPUnit\Framework\MockObject\MockObject;
use InterNations\DataGridBundle\Grid\Column\Column;
use InterNations\DataGridBundle\Grid\Column\TextColumn;
use InterNations\DataGridBundle\Grid\Grid;
use InterNations\DataGridBundle\Grid\Row;
use InterNations\DataGridBundle\Twig\DataGridExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Runtime\EscaperRuntime;
use Twig\Template;
use Twig\TemplateWrapper;

class DataGridExtensionTest extends TestCase
{
    /** @var UrlGeneratorInterface|MockObject */
    private $urlGenerator;

    /** @var Environment|MockObject */
    private $environment;

    /** @var Column */
    private $column;

    /** @var Row */
    private $row;

    /** @var Grid|MockObject */
    private $grid;

    /** @var DataGridExtension */
    private $extension;

    /** @var array */
    private $context = ['ctxt1' => 'ctxt1'];

    public function setUp(): void
    {
        // Trigger autoload
        new CoreExtension();

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->environment = $this->createMock(Environment::class);
        $this->environment
            ->method('mergeGlobals')
            ->willReturnArgument(0);
        $this->environment
            ->method('getRuntime')
            ->with(EscaperRuntime::class)
            ->willReturn(new EscaperRuntime());
        $this->grid = $this->createConfiguredMock(Grid::class, ['getHash' => 'hash', 'getId' => 'ID']);
        $this->column = new TextColumn();
        $this->row = new Row();

        $routes = new RouteCollection();
        $routes->add('grid', new Route('/grid'));
        $this->urlGenerator = new UrlGenerator($routes, new RequestContext());

        $this->extension = new DataGridExtension($this->urlGenerator);
    }

    public static function getSortingUrlExamples()
    {
        return [
            [
                'http://localhost/grid?hash[_order]=id|asc',
                ['hash' => ['_order' => 'id|asc']],
                new TextColumn(['id' => 'id'])
            ],
            [
                'http://localhost/grid?hash[_order]=id|desc',
                ['hash' => ['_order' => 'id|desc']],
                (new TextColumn(['id' => 'id', 'order' => 'asc']))->setOrder('asc')
            ],
            [
                'http://localhost/grid?hash[_order]=id|asc',
                ['hash' => ['_order' => 'id|asc']],
                (new TextColumn(['id' => 'id', 'order' => 'desc']))->setOrder('desc')
            ],
        ];
    }

    /** @dataProvider getSortingUrlExamples */
    public function testGetGridSortingUrl($expectedUrl, array $expectedParams, Column $columns = null)
    {
        $this->mockGridGetRouteUrl($expectedParams);

        $this->assertSame($expectedUrl, urldecode($this->extension->getGridSortingUrl($this->grid, $columns)));
    }

    public function testGetPaginationUrl()
    {
        $this->mockGridGetRouteUrl(['hash' => ['_page' => '2']]);

        $this->assertSame(
            'http://localhost/grid?hash[_page]=2',
            urldecode($this->extension->getGridPaginationUrl($this->grid, 2))
        );
    }

    public function testGetPaginationUrlWithEmptyPage()
    {
        $this->mockGridGetRouteUrl(['hash' => ['_page' => '']]);

        $this->assertSame(
            'http://localhost/grid?hash[_page]=',
            urldecode($this->extension->getGridPaginationUrl($this->grid))
        );
    }

    public function testGetGridLimitUrl()
    {
        $this->mockGridGetRouteUrl(['hash' => ['_limit' => '']]);

        $this->assertSame(
            'http://localhost/grid?hash[_limit]=',
            urldecode($this->extension->getGridLimitUrl($this->grid))
        );
    }

    public function testGetGridCellWithCustomBlock()
    {
        $template = $this->createMock(Template::class);
        $template
            ->method('hasBlock')
            ->willReturnMap(
                [
                    ['grid_ID_column_test_cell', $this->context, [], false],
                    ['grid_column_test_cell', $this->context, [], true]
                ]
            );

        $template
            ->expects($this->once())
            ->method('displayBlock')
            ->with(
                'grid_column_test_cell',
                ['grid' => $this->grid, 'column' => $this->column, 'value' => 'value', 'row' => $this->row]
                + $this->context
            )
            ->willReturnCallback(
                static function () {
                    echo 'rendered';
                }
            );

        $this->environment
            ->expects($this->once())
            ->method('load')
            ->with(DataGridExtension::DEFAULT_TEMPLATE)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        $this->row->setField('test', 'value');
        $this->column->setId('test');

        $this->assertSame(
            'rendered',
            $this->extension->getGridCell($this->environment, $this->context, $this->column, $this->row, $this->grid)
        );
    }

    public function testGetGridCellWithThemeBlock(): void
    {
        $template = $this->createMock(Template::class);
        $template
            ->method('hasBlock')
            ->willReturnMap(
                [
                    ['grid_ID_column_test_cell', $this->context, [], true],
                ]
            );
        $template
            ->expects($this->once())
            ->method('renderBlock')
            ->with(
                'grid_ID_column_test_cell',
                ['grid' => $this->grid, 'column' => $this->column, 'value' => 'value', 'row' => $this->row]
                + $this->context
            )
            ->willReturn('rendered');

        $this->environment
            ->expects($this->once())
            ->method('load')
            ->with(DataGridExtension::DEFAULT_TEMPLATE)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        $this->row->setField('test', 'value');
        $this->column->setId('test');

        $this->assertSame(
            'rendered',
            $this->extension->getGridCell($this->environment, $this->context, $this->column, $this->row, $this->grid)
        );
    }

    public function testGetGridCellDefault(): void
    {
        $template = $this->createMock(Template::class);
        $template
            ->method('hasBlock')
            ->willReturn(false);

        $this->environment
            ->expects($this->once())
            ->method('load')
            ->with(DataGridExtension::DEFAULT_TEMPLATE)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        $this->row->setField('test', 'value');
        $this->column->setId('test');

        $this->assertSame(
            'value',
            $this->extension->getGridCell($this->environment, $this->context, $this->column, $this->row, $this->grid)
        );
    }

    public function testGetGridCellWithNullValue(): void
    {
        $template = $this->createMock(Template::class);
        $template
            ->method('hasBlock')
            ->willReturn(false);

        $this->environment
            ->expects($this->once())
            ->method('load')
            ->with(DataGridExtension::DEFAULT_TEMPLATE)
            ->willReturn(new TemplateWrapper($this->environment, $template));

        $this->column->setCallback(function () { return null; });

        $this->assertNull($this->extension->getGridCell($this->environment, $this->context, $this->column, $this->row, $this->grid));
    }

    private function mockGridGetRouteUrl(array $expectedParams)
    {
        $this->grid
            ->expects($this->once())
            ->method('getRouteUrl')
            ->willReturnCallback(
                function(array $params = []) use ($expectedParams) {
                    $this->assertSame($expectedParams, $params);
                    return $this->urlGenerator->generate('grid', $params, UrlGeneratorInterface::ABSOLUTE_URL);
                }
            );
    }
}
