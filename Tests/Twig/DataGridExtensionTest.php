<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Tests\Twig;

use InterNations\Component\Testing\AbstractTestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Sorien\DataGridBundle\Grid\Column\Column;
use Sorien\DataGridBundle\Grid\Column\TextColumn;
use Sorien\DataGridBundle\Grid\Grid;
use Sorien\DataGridBundle\Twig\DataGridExtension;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig_Environment as TwigEnvironment;

class DataGridExtensionTest extends AbstractTestCase
{
    /** @var UrlGeneratorInterface|MockObject */
    private $urlGenerator;

    /** @var  TwigEnvironment|MockObject */
    private $environment;

    /** @var Grid|MockObject */
    private $grid;

    /** @var DataGridExtension */
    private $extension;

    public function setUp()
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->environment = $this->createMock(TwigEnvironment::class);
        $this->grid = $this->createMock(Grid::class);
        $this->grid
            ->expects($this->any())
            ->method('getHash')
            ->willReturn('hash');

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
        $this->mockGridGetRouteUrl(['hash' => ['_page' => 2]]);

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
