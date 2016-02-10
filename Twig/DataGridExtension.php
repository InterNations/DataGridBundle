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

use Sorien\DataGridBundle\Grid\Grid;

class DataGridExtension extends \Twig_Extension
{
    const DEFAULT_TEMPLATE = 'SorienDataGridBundle::blocks.html.twig';

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @var \Twig_TemplateInterface[]
     */
    protected $templates;

    /**
     * @var string
     */
    protected $theme;

    /**
    * @var \Symfony\Component\Routing\Router
    */
    protected $router;

    /**
     * @var string[]
     */
    protected $names;

    /**
     * @param \Symfony\Component\Routing\Router $router
     */
    public function __construct($router)
    {
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'grid',
                [$this, 'getGrid'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'grid_titles',
                [$this, 'getGridTitles'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'grid_filters',
                [$this, 'getGridFilters'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'grid_rows',
                [$this, 'getGridItems'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'grid_pager',
                [$this, 'getGridPager'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction(
                'grid_actions',
                [$this, 'getGridActions'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true,
                ]
            ),
            new \Twig_SimpleFunction('grid_url', [$this, 'getGridUrl']),
            new \Twig_SimpleFunction('grid_filter', [$this, 'getGridFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFunction(
                'grid_cell',
                [$this, 'getGridCell'],
                [
                    'is_safe'           => ['html'],
                    'needs_environment' => true
                ]
            ),
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param \Sorien\DataGridBundle\Grid\Grid $grid
     * @param string $theme
     * @param string $id
     * @return string
     */
    public function getGrid(\Twig_Environment $environment, $grid, $theme = null, $id = '')
    {
        $this->theme = $theme;
        $this->names[$grid->getHash()] = $id == '' ? $grid->getId() : $id;

        return $this->renderBlock($environment, 'grid', array('grid' => $grid->prepare()));
    }

    public function getGridTitles(\Twig_Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_titles', array('grid' => $grid));
    }

    public function getGridFilters(\Twig_Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_filters', array('grid' => $grid));
    }

    public function getGridItems(\Twig_Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_rows', array('grid' => $grid));
    }

    public function getGridPager(\Twig_Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_pager', array('grid' => $grid));
    }

    public function getGridActions(\Twig_Environment $environment, $grid)
    {
        return $this->renderBlock($environment, 'grid_actions', array('grid' => $grid));
    }

    /**
     * @param \Twig_Environment $environment
     * @param \Sorien\DataGridBundle\Grid\Column\Column $column
     * @param \Sorien\DataGridBundle\Grid\Row $row
     * @param \Sorien\DataGridBundle\Grid\Grid $grid
     *
     * @return string
     */
    public function getGridCell(\Twig_Environment $environment, $column, $row, $grid)
    {
        $value = $column->renderCell($row->getField($column->getId()), $row, $this->router);

        if (($id = $this->names[$grid->getHash()]) != '')
        {
            if ($this->hasBlock($environment, $block = 'grid_'.$id.'_column_'.$column->getId().'_cell'))
            {
                return $this->renderBlock($environment, $block, array('column' => $column, 'value' => $value, 'row' => $row));
            }
        }

        if ($this->hasBlock($environment, $block = 'grid_column_'.$column->getId().'_cell'))
        {
            return $this->renderBlock($environment, $block, array('column' => $column, 'value' => $value, 'row' => $row));
        }

        return $value;
    }

    /**
     * @param \Twig_Environment $environment
     * @param \Sorien\DataGridBundle\Grid\Column\Column $column
     * @param \Sorien\DataGridBundle\Grid\Grid $grid
     *
     * @return string
     */
    public function getGridFilter(\Twig_Environment $environment, $column, $grid)
    {
        if (($id = $this->names[$grid->getHash()]) != '')
        {
            if ($this->hasBlock($environment, $block = 'grid_'.$id.'_column_'.$column->getId().'_filter'))
            {
                return $this->renderBlock($environment, $block, array('column' => $column, 'hash' => $grid->getHash()));
            }
        }

        if ($this->hasBlock($environment, $block = 'grid_column_'.$column->getId().'_filter'))
        {
            return $this->renderBlock($environment, $block, array('column' => $column, 'hash' => $grid->getHash()));
        }

        return $column->renderFilter($grid->getHash());
    }

    /**
     * @param string $section
     * @param \Sorien\DataGridBundle\Grid\Grid $grid
     * @param \Sorien\DataGridBundle\Grid\Column\Column $param
     * @return string
     */
    public function getGridUrl($section, $grid, $param = null)
    {
        if ($section == 'order')
        {
            if ($param->isSorted())
            {
                return $grid->getRouteUrl().'?'.$grid->getHash().'['.Grid::REQUEST_QUERY_ORDER.']='.$param->getId().'|'.($param->getOrder() == 'asc' ? 'desc' : 'asc');
            }
            else
            {
                return $grid->getRouteUrl().'?'.$grid->getHash().'['.Grid::REQUEST_QUERY_ORDER.']='.$param->getId().'|asc';
            }
        }
        elseif ($section == 'page')
        {
            return $grid->getRouteUrl().'?'.$grid->getHash().'['.Grid::REQUEST_QUERY_PAGE.']='.$param;
        }
        elseif ($section == 'limit')
        {
            return $grid->getRouteUrl().'?'.$grid->getHash().'['.Grid::REQUEST_QUERY_LIMIT.']=';
        }
    }

    /**
     * @param \Twig_Environment $environment
     * @param $name string
     * @param $parameters string
     * @return string
     */
    private function renderBlock(\Twig_Environment $environment, $name, $parameters)
    {
        foreach ($this->getTemplates($environment) as $template)
        {
            if ($template->hasBlock($name))
            {
                $rendered = $template->renderBlock($name, $parameters);
                return $rendered;
            }
        }

        throw new \InvalidArgumentException(sprintf('Block "%s" doesn\'t exist in grid template "%s".', $name, $this->theme));
    }

    /**
     * @param \Twig_Environment $environment
     * @param $name string
     * @return boolean
     */
    private function hasBlock(\Twig_Environment $environment, $name)
    {
        foreach ($this->getTemplates($environment) as $template)
        {
            if ($template->hasBlock($name))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Twig_Environment $environment
     * @return \Twig_TemplateInterface[]
     * @throws \Exception
     */
    private function getTemplates(\Twig_Environment $environment)
    {
        if (empty($this->templates))
        {
            //get template name
            if ($this->theme instanceof \Twig_Template)
            {
                $this->templates[] = $this->theme;
                $this->templates[] = $environment->loadTemplate($this::DEFAULT_TEMPLATE);
            }
            elseif (is_string($this->theme))
            {
                $template = $environment->loadTemplate($this->theme);
                while ($template != null)
                {
                    $this->templates[] = $template;
                    $template = $template->getParent(array());
                }

                $this->templates[] = $environment->loadTemplate($this->theme);
            }
            elseif (is_null($this->theme))
            {
                $this->templates[] = $environment->loadTemplate($this::DEFAULT_TEMPLATE);
            }
            else
            {
                throw new \Exception('Unable to load template');
            }
        }

        return $this->templates;
    }

    public function getName()
    {
        return 'datagrid_twig_extension';
    }
}
