<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Grid;

use InvalidArgumentException;
use Sorien\DataGridBundle\Grid\Column\Column;
use Sorien\DataGridBundle\Grid\Helper\ColumnsIterator;

class Columns implements \IteratorAggregate, \Countable
{
    /**
     * @var \Sorien\DataGridBundle\Grid\Column\Column[]
     */
    private $columns = [];
    private $extensions;

    public function getIterator($showOnlySourceColumns = false)
    {
        return new ColumnsIterator(new \ArrayIterator($this->columns), $showOnlySourceColumns);
    }

    public function getPrimaryColumn()
    {
        foreach ($this->columns as $column)
        {
            if ($column->isPrimary())
            {
                return $column;
            }
        }

        throw new InvalidArgumentException('Primary column doesn\'t exists');
    }
    public function addExtension($extension)
    {
        $this->extensions[strtolower($extension->getType())] = $extension;
    }

    public function hasExtensionForColumnType($type)
    {
        return isset($this->extensions[$type]);
    }

    public function getExtensionForColumnType($type)
    {
        return $this->extensions[$type];
    }

    // THE GOOD STUFF

    /**
     * Internal function
     * @return string
     */
    public function getHash()
    {
        $input = '';
        foreach ($this->columns as $column) {
            $input .= '__COLUMN:' . $column->getId();
        }

        return hash('sha256', $input);
    }

    /**
     * @param Column $column
     * @param int $position
     * @return Grid
     */
    public function addColumn(Column $column, $position = null)
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

    /**
     * @param string $columnId
     * @throws InvalidArgumentException
     * @return Column
     */
    public function getColumnById($columnId)
    {
        $column = $this->getColumnByIdOrNull($columnId);

        if ($column === null) {
            throw new InvalidArgumentException(sprintf('Column with ID "%s" does not exists', $columnId));
        }

        return $column;
    }

    /**
     * @param string $columnId
     * @return bool
     */
    public function hasColumnById($columnId)
    {
        return (bool) $this->getColumnByIdOrNull($columnId);
    }

    /**
     * @param string $columnId
     * @return Column|null
     */
    public function getColumnByIdOrNull($columnId)
    {
        foreach ($this->columns as $column) {
            if ($column->getId() === $columnId) {
                return $column;
            }
        }

        return null;
    }

    public function count()
    {
        return count($this->columns);
    }
}
