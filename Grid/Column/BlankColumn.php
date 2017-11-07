<?php

/*
 * This file is part of the DataGridBundle.
 *
 * (c) Stanislav Turza <sorien@mail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Grid\Column;

class BlankColumn extends Column
{
    public function initialize(array $params): void
    {
        parent::initialize(array_merge(['sortable' => false, 'filterable' => false, 'source' => false], $params));
    }
}
