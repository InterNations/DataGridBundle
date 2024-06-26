<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace InterNations\DataGridBundle\Grid;

use Closure;
use Psr\Container\ContainerInterface;
use InterNations\DataGridBundle\Grid\Action\MassAction;
use InterNations\DataGridBundle\Grid\Action\MassActionInterface;
use InterNations\DataGridBundle\Grid\Action\RowActionInterface;
use InterNations\DataGridBundle\Grid\Column\ActionsColumn;
use InterNations\DataGridBundle\Grid\Column\Column;
use InterNations\DataGridBundle\Grid\Column\MassActionColumn;
use InterNations\DataGridBundle\Grid\Column\SelectColumn;
use InterNations\DataGridBundle\Grid\Source\Source;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;

class Grid
{
    const REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED = '__action_all_keys';
    const REQUEST_QUERY_MASS_ACTION = '__action_id';
    const REQUEST_QUERY_PAGE = '_page';
    const REQUEST_QUERY_LIMIT = '_limit';
    const REQUEST_QUERY_ORDER = '_order';

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $routeParameters;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var Source
     */
    private $source;

    private $totalCount;

    private $page;

    private $limit;

    private $limits;

    /**
     * @var Columns
     */
    private $columns;

    /**
     * @var Rows
     */
    private $rows;

    /**
     * @var MassAction[]
     */
    private $massActions;

    private $rowActions;

    /**
     * @var boolean
     */
    private $showFilters;

    /**
     * @var boolean
     */
    private $showTitles;

    public function __construct(ContainerInterface $container, Source $source = null, Columns $columns = null)
    {
        $this->container = $container;

        $this->router = $container->get('router');
        $this->request = $container->get('request_stack')->getMasterRequest();
        $this->session = $this->request->getSession();

        $this->id = '';

        $this->setLimits([20 => '20', 50 => '50', 100 => '100']);
        $this->page = 0;
        $this->showTitles = $this->showFilters = true;

        $this->columns = $columns ?? new Columns();
        $this->massActions = [];
        $this->rowActions = [];

        $this->routeParameters = $this->request->attributes->all();
        unset($this->routeParameters['_route']);
        unset($this->routeParameters['_controller']);
        unset($this->routeParameters['_route_params']);
        unset($this->routeParameters['_template']);
        unset($this->routeParameters['_template_streamable']);
        unset($this->routeParameters['_template_default_vars']);
        unset($this->routeParameters['_firewall_context']);

        if ($source) {
            $this->setSource($source);
        }
    }

    public function addMassAction(MassActionInterface $action): self
    {
        if ($this->source instanceof Source) {
            throw new \RuntimeException('The actions have to be defined before the source.');
        }
        $this->massActions[] = $action;

        return $this;
    }

    public function addRowAction(RowActionInterface $action): self
    {
        $this->rowActions[$action->getColumn()][] = $action;

        return $this;
    }

    public function setSource(Source $source): void
    {
        $this->source = $source;

        $this->source->initialise($this->container);
        $this->source->getColumns($this->columns);
        $this->createHash();
        $this->fetchAndSaveColumnData();
        $this->executeMassActions();
        $this->fetchAndSaveGridData();
    }

    private function getData(string $column, bool $fromRequest = true, bool $fromSession = true)
    {
        $result = null;

        if ($fromSession && is_array($data = $this->session->get($this->getHash()))) {
            if (isset($data[$column])) {
                $result = $data[$column];
            }
        }

        if ($fromRequest && is_array($data = $this->request->get($this->getHash()))) {
            if (isset($data[$column])) {
                $result = $data[$column];
            }
        }

        if ($fromRequest && $this->request->get($column, $this) !== $this) {
            $columnObject = $this->getColumns()->getColumnById($column);
            if ($columnObject instanceof SelectColumn) {
                $result = array_unique(explode(',', $this->request->get($column)));
            } else {
                $result = $this->request->get($column);
            }
        }

        return $result;
    }

    private function fetchAndSaveColumnData(): void
    {
        $storage = $this->session->get($this->getHash());

        $useSession = true;
        foreach ($this->columns as $column) {
            if ($this->request->get($column->getId())) {
                $useSession = false;
                break;
            }
        }

        foreach ($this->columns as $column) {
            $column->setData($this->getData($column->getId(), true, $useSession));

            if (($data = $column->getData()) !== null) {
                $storage[$column->getId()] = $data;
            } else {
                unset($storage[$column->getId()]);
            }
        }

        if (!empty($storage)) {
            $this->session->set($this->getHash(), $storage);
        }
    }

    private function fetchAndSaveGridData(): void
    {
        $storage = $this->session->get($this->getHash());

        //set internal data
        if ($limit = $this->getData(self::REQUEST_QUERY_LIMIT)) {
            $this->limit = $limit;
        }

        if ($page = $this->getData(self::REQUEST_QUERY_PAGE)) {
            $this->setPage($page);
        }

        if (!is_null($order = $this->getData(self::REQUEST_QUERY_ORDER))) {
            [$columnId, $columnOrder] = explode('|', $order);

            $column = $this->columns->getColumnById($columnId);
            if (!is_null($column)) {
                $column->setOrder($columnOrder);
            }

            $storage[self::REQUEST_QUERY_ORDER] = $order;
        }

        if ($this->getCurrentLimit() != $this->getData(self::REQUEST_QUERY_LIMIT, false)
            && $this->getCurrentLimit() >= 0) {
            $storage[self::REQUEST_QUERY_LIMIT] = $this->getCurrentLimit();
        }

        if ($this->getPage() >= 0) {
            $storage[self::REQUEST_QUERY_PAGE] = $this->getPage();
        }

        // save data to sessions if needed
        if (!empty($storage)) {
            $this->session->set($this->getHash(), $storage);
        }
    }

    public function executeMassActions(): void
    {
        $actionId = $this->getData(Grid::REQUEST_QUERY_MASS_ACTION, true, false);
        $actionAllKeys = (boolean) $this->getData(Grid::REQUEST_QUERY_MASS_ACTION_ALL_KEYS_SELECTED, true, false);
        $actionKeys = $actionAllKeys == false ? $this->getData(MassActionColumn::ID, true, false): [];

        if ($actionId > -1 && is_array($actionKeys)) {
            if (array_key_exists($actionId, $this->massActions)) {
                $action = $this->massActions[$actionId];

                $callback = $action->getCallback();

                if (is_callable($callback)) {
                    Closure::fromCallable($callback)->call(
                        $this,
                        array_keys($actionKeys),
                        $actionAllKeys,
                        $this->session,
                        $action->getParameters()
                    );
                } elseif (substr_count($action->getCallback(), ':') > 0) {
                    $this->container->get('http_kernel')->forward(
                        $action->getCallback(),
                        array_merge(
                            ['primaryKeys' => array_keys($actionKeys), 'allPrimaryKeys' => $actionAllKeys],
                            $action->getParameters()
                        )
                    );
                } else {
                    throw new \RuntimeException(
                        sprintf('Callback %s is not callable or Controller action', $action->getCallback())
                    );
                }
            } else {
                throw new \OutOfBoundsException(sprintf('Action %s is not defined.', $actionId));
            }
        }
    }

    /**
     * Prepare Grid for Drawing
     *
     * @return Grid
     */
    public function prepare()
    {
        $this->rows = $this->source->execute($this->columns->getIterator(true), $this->page, $this->limit);

        if (!$this->rows instanceof Rows) {
            throw new \Exception('Source have to return Rows object.');
        }

        //add row actions column
        if (count($this->rowActions) > 0) {
            foreach ($this->rowActions as $column => $rowActions) {
                if ($rowAction = $this->columns->getColumnByIdOrNull($column)) {
                    $rowAction->setRowActions($rowActions);
                } else {
                    $this->columns->addColumn(new ActionsColumn($column, 'Actions', $rowActions));
                }
            }
        }

        //add mass actions column
        if (count($this->massActions) > 0) {
            $this->columns->addColumn($this->createMassActionColumn(), 1);
        }

        $primaryColumnId = $this->columns->getPrimaryColumn()->getId();

        foreach ($this->rows as $row) {
            foreach ($this->columns as $column) {
                $row->setPrimaryField($primaryColumnId);
            }
        }

        //@todo refactor autohide titles when no title is set
        if (!$this->showTitles) {
            $this->showTitles = false;
            foreach ($this->columns as $column) {
                if (!$this->showTitles) {
                    break;
                }

                if ($column->getTitle() != '') {
                    $this->showTitles = true;
                    break;
                }
            }
        }

        //get size
        if (!is_int($this->totalCount = $this->source->getTotalCount($this->columns))) {
            throw new \Exception(
                sprintf(
                    'Source function getTotalCount need to return integer result, returned: %s',
                    gettype($this->totalCount)
                )
            );
        }

        return $this;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns($columns)
    {
        if (!$columns instanceof Columns) {
            throw new \InvalidArgumentException('Supplied object have to extend Columns class.');
        }

        $this->columns = $columns;
        $this->fetchAndSaveColumnData();

        return $this;
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function getMassActions()
    {
        return $this->massActions;
    }

    public function getRowActions()
    {
        return $this->rowActions;
    }

    public function setRouteParameter($parameter, $value)
    {
        $this->routeParameters[$parameter] = $value;
    }

    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    public function getRouteUrl(array $params = [])
    {
        return $this->router->generate(
            $this->request->get('_route'),
            array_replace($this->getRouteParameters(), $params)
        );
    }

    public function isReadyForRedirect()
    {
        $data = $this->request->get($this->getHash());

        return !empty($data);
    }

    public function createHash()
    {
        $this->hash = 'grid_' . md5(
                $this->request->get('_controller')
                . $this->columns->getHash()
                . $this->source->getHash()
                . $this->getId()
            );
    }

    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $limits
     * @return \InterNations\DataGridBundle\Grid\Grid
     */
    public function setLimits($limits)
    {
        if (is_array($limits)) {
            $this->limits = $limits;
            $this->limit = (int) key($this->limits);
        } elseif (is_int($limits)) {
            $this->limits = [$limits => (string) $limits];
            $this->limit = $limits;
        } else {
            throw new \InvalidArgumentException('Limit has to be array or integer');
        }

        return $this;
    }

    public function getLimits()
    {
        return $this->limits;
    }

    public function getCurrentLimit()
    {
        return $this->limit;
    }

    public function setPage($page)
    {
        if ((int) $page > 0) {
            $this->page = (int) $page;
        } else {
            throw new \InvalidArgumentException('Page has to have a positive number');
        }

        return $this;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getPageCount()
    {
        return ceil($this->getTotalCount() / $this->getCurrentLimit());
    }

    public function getTotalCount()
    {
        return $this->totalCount;
    }

    public function isTitleSectionVisible(): bool
    {
        return $this->showTitles;
    }

    public function isFilterSectionVisible(): bool
    {
        if ($this->showFilters) {
            foreach ($this->columns as $column) {
                if ($column->isFilterable()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isPagerSectionVisible(): bool
    {
        $limits = count($this->getLimits());

        return $limits > 1 || ($limits === 0 && $this->getCurrentLimit() < $this->totalCount);
    }

    public function hideFilters(): void
    {
        $this->showFilters = false;
    }

    public function hideTitles(): void
    {
        $this->showTitles = false;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function deleteAction(array $ids): void
    {
        $this->source->delete($ids);
    }

    public function __clone()
    {
        $this->columns = clone $this->columns;
    }

    /** @return Response|array A Response instance */
    public function gridResponse(array $parameters = [], string $view = null, Response $response = null)
    {
        if ($this->isReadyForRedirect()) {
            return new RedirectResponse($this->getRouteUrl());
        }

        if (!$view) {
            return $parameters;
        } else {
            $response ??= new Response();
            $response->setContent($this->container->get('twig')->render($view, $parameters));
            return $response;
        }
    }

    protected function createMassActionColumn()
    {
        return new MassActionColumn($this->getHash());
    }

    public function addColumn(Column $column, ?int $position = null): self
    {
        $this->columns->addColumn($column, $position);

        return $this;
    }
}
