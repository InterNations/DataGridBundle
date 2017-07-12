<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Twig;

use InvalidArgumentException;
use Sorien\DataGridBundle\Grid\Column\Column;
use Sorien\DataGridBundle\Grid\Grid;
use Sorien\DataGridBundle\Grid\Row;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig_Environment as Environment;
use Twig\Template;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class DataGridExtension extends AbstractExtension
{
    const DEFAULT_TEMPLATE = 'SorienDataGridBundle::blocks.html.twig';

    /** @var Template[] */
    private $templates = [];

    /** @var string */
    private $theme;

    /* @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'grid',
                [$this, 'getGrid'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'grid_titles',
                [$this, 'getGridTitles'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'grid_filters',
                [$this, 'getGridFilters'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'grid_rows',
                [$this, 'getGridItems'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'grid_pager',
                [$this, 'getGridPager'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction(
                'grid_actions',
                [$this, 'getGridActions'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new TwigFunction('grid_limit_url', [$this, 'getGridLimitUrl']),
            new TwigFunction('grid_pagination_url', [$this, 'getGridPaginationUrl']),
            new TwigFunction('grid_sorting_url', [$this, 'getGridSortingUrl']),
            new TwigFunction('grid_filter', [$this, 'getGridFilter'], ['needs_environment' => true]),
            new TwigFunction(
                'grid_cell',
                [$this, 'getGridCell'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * @param Environment $environment
     * @param Grid $grid
     * @param string $theme
     * @param string $id
     * @return string
     */
    public function getGrid(Environment $environment, $grid, $theme = null)
    {
        $this->theme = $theme;

        return $this->renderBlock($environment, 'grid', array('grid' => $grid->prepare()));
    }

    private static function getGridId(Grid $grid): string
    {
        return $grid->getId() ?: '';
    }

    public function getGridTitles(Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_titles', array('grid' => $grid));
    }

    public function getGridFilters(Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_filters', array('grid' => $grid));
    }

    public function getGridItems(Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_rows', array('grid' => $grid));
    }

    public function getGridPager(Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_pager', array('grid' => $grid));
    }

    public function getGridActions(Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_actions', array('grid' => $grid));
    }

    public function getGridCell(Environment $environment, Column $column, Row $row, Grid $grid): ?string
    {
        $value = $column->renderCell($row->getField($column->getId()), $row, $this->urlGenerator);

        $id = self::getGridId($grid);
        if ($id !== '') {
            if ($this->hasBlock($environment, $block = 'grid_' . $id . '_column_' . $column->getId() . '_cell')) {
                return $this->renderBlock(
                    $environment,
                    $block,
                    ['grid' => $grid, 'column' => $column, 'value' => $value, 'row' => $row]
                );
            }
        }

        $block = 'grid_column_' . $column->getId() . '_cell';
        if ($this->hasBlock($environment, $block)) {
            return $this->renderBlock(
                $environment,
                $block,
                ['grid' => $grid, 'column' => $column, 'value' => $value, 'row' => $row]
            );
        }

        return $value;
    }

    public function getGridFilter(Environment $environment, Column $column, Grid $grid): string
    {
        $id = self::getGridId($grid);

        if ($id !== '') {
            if ($this->hasBlock($environment, $block = 'grid_' . $id . '_column_' . $column->getId() . '_filter')) {
                return $this->renderBlock($environment, $block, ['column' => $column, 'hash' => $grid->getHash()]);
            }
        }

        if ($this->hasBlock($environment, $block = 'grid_column_' . $column->getId() . '_filter')) {
            return $this->renderBlock($environment, $block, array('column' => $column, 'hash' => $grid->getHash()));
        }

        return $column->renderFilter($grid->getHash());
    }

    private function renderBlock(Environment $environment, string $name, array $parameters): string
    {
        foreach ($this->getTemplates($environment) as $template) {
            if ($template->hasBlock($name)) {
                return $template->renderBlock($name, $parameters);
            }
        }

        throw new InvalidArgumentException(
            sprintf('Block "%s" doesn’t exist in grid template "%s".', $name, $this->theme)
        );
    }

    private function hasBlock(Environment $environment, string $name): bool
    {
        foreach ($this->getTemplates($environment) as $template) {
            if ($template->hasBlock($name)) {
                return true;
            }
        }

        return false;
    }

    /** @return Template[] */
    private function getTemplates(Environment $environment): array
    {
        if (empty($this->templates)) {
            //get template name
            if ($this->theme instanceof Template) {
                $this->templates[] = $this->theme;
                $this->templates[] = $environment->loadTemplate($this::DEFAULT_TEMPLATE);
            } elseif (is_string($this->theme)) {
                $template = $environment->loadTemplate($this->theme);
                while ($template !== null) {
                    $this->templates[] = $template;
                    $template = $template->getParent([]);
                }

                $this->templates[] = $environment->loadTemplate($this->theme);
            } elseif ($this->theme === null) {
                $this->templates[] = $environment->loadTemplate($this::DEFAULT_TEMPLATE);
            } else {
                throw new \Exception('Unable to load template');
            }
        }

        return $this->templates;
    }

    // The good refactored and tested parts start here

    public function getGridSortingUrl(Grid $grid, Column $column): string
    {
        $order = 'asc';
        if ($column->isSorted()) {
            $order = $column->getOrder() === 'asc' ? 'desc' : 'asc';
        }

        return $grid->getRouteUrl(
            [$grid->getHash() => [Grid::REQUEST_QUERY_ORDER => $column->getId() . '|' . $order]]
        );
    }

    public function getGridLimitUrl(Grid $grid): string
    {
        return $grid->getRouteUrl([$grid->getHash() => [Grid::REQUEST_QUERY_LIMIT => '']]);
    }

    public function getGridPaginationUrl(Grid $grid, int $page = null): string
    {
        return $grid->getRouteUrl([$grid->getHash() => [Grid::REQUEST_QUERY_PAGE => $page]]);
    }
}
