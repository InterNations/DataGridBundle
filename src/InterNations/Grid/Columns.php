<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Grid;

use Countable;
use InvalidArgumentException;
use InterNations\DataGridBundle\Grid\Column\Column;
use InterNations\DataGridBundle\Grid\Helper\ColumnsIterator;

class Columns implements \IteratorAggregate, Countable
{
    /** @var Column[] */
    private $columns = [];

    public function getIterator(bool $showOnlySourceColumns = false): ColumnsIterator
    {
        return new ColumnsIterator(new \ArrayIterator($this->columns), $showOnlySourceColumns);
    }

    public function getPrimaryColumn(): Column
    {
        foreach ($this->columns as $column) {
            if ($column->isPrimary()) {
                return $column;
            }
        }

        throw new InvalidArgumentException('Primary column doesn\'t exists');
    }

    public function getHash(): string
    {
        $input = '';

        foreach ($this->columns as $column) {
            $input .= '__COLUMN:' . $column->getId();
        }

        return hash('sha256', $input);
    }

    public function addColumn(Column $column, ?int $position = null): self
    {
        if ($position !== null) {
            $index = max(0, $position - 1);
            $head = array_slice($this->columns, 0, $index);
            $tail = array_slice($this->columns, $index);
            $this->columns = array_merge($head, [$column], $tail);
        } else {
            $this->columns[] = $column;
        }

        return $this;
    }

    public function getColumnById(string $columnId): Column
    {
        $column = $this->getColumnByIdOrNull($columnId);

        if ($column === null) {
            throw new InvalidArgumentException(sprintf('Column with ID "%s" does not exists', $columnId));
        }

        return $column;
    }

    public function hasColumnById(string $columnId): bool
    {
        return (bool) $this->getColumnByIdOrNull($columnId);
    }

    public function getColumnByIdOrNull(string $columnId): ?Column
    {
        foreach ($this->columns as $column) {
            if ($column->getId() === $columnId) {
                return $column;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->columns);
    }
}
