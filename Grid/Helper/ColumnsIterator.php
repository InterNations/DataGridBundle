<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Grid\Helper;

use FilterIterator;
use Sorien\DataGridBundle\Grid\Column\Column;
use Traversable;

class ColumnsIterator extends FilterIterator
{
    private $showOnlySourceColumns;

    public function __construct(Traversable $iterator, bool $showOnlySourceColumns)
    {
        parent::__construct($iterator);
        $this->showOnlySourceColumns = $showOnlySourceColumns;
    }

    public function accept(): bool
    {
        return $this->showOnlySourceColumns ? $this->getCurrentColumn()->isVisibleForSource() : true;
    }

    private function getCurrentColumn(): Column
    {
        return $this->getInnerIterator()->current();
    }
}
