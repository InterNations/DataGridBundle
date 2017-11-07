<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Grid\Source;

use Psr\Container\ContainerInterface;
use Sorien\DataGridBundle\Grid\Columns;
use Sorien\DataGridBundle\Grid\Helper\ColumnsIterator;
use Sorien\DataGridBundle\Grid\Row;
use Sorien\DataGridBundle\Grid\Rows;

abstract class Source
{
    const EVENT_PREPARE = 0;
    const EVENT_PREPARE_QUERY = 1;
    const EVENT_PREPARE_COUNT_QUERY = 3;
    const EVENT_PREPARE_ROW = 2;

    private $callbacks;

    public function prepareQuery($queryBuilder)
    {
        if (is_callable($this->callbacks[$this::EVENT_PREPARE_QUERY] ?? null)) {
            return call_user_func($this->callbacks[$this::EVENT_PREPARE_QUERY], $queryBuilder);
        }

        return $queryBuilder;
    }

    public function prepareCountQuery($queryBuilder)
    {
        if (is_callable($this->callbacks[$this::EVENT_PREPARE_COUNT_QUERY] ?? null)) {
            return call_user_func($this->callbacks[$this::EVENT_PREPARE_COUNT_QUERY], $queryBuilder);
        }

        return $queryBuilder;
    }

    public function prepareRow(Row $row): ?Row
    {
        if (is_callable($this->callbacks[$this::EVENT_PREPARE_ROW] ?? null)) {
            return call_user_func($this->callbacks[$this::EVENT_PREPARE_ROW], $row);
        }

        return $row;
    }

    public function setCallback(string $type, callable $callback): void
    {
        $this->callbacks[$type] = $callback;
    }

    abstract public function execute(ColumnsIterator $columns, int $page = 0, int $limit = 0): Rows;

    abstract public function getTotalCount(Columns $columns): int;

    abstract public function initialise(ContainerInterface $container);

    public function getColumns(Columns $columns): void
    {
    }

    public function getClassColumns(string $class): array
    {
        return [];
    }

    public function getFieldsMetadata(string $class): array
    {
        return [];
    }

    abstract public function getHash(): string;

    abstract public function delete(array $ids): void;
}
