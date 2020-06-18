<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace InterNations\DataGridBundle\Tests\Grid\Column;

use InterNations\Component\Testing\AbstractTestCase;
use InterNations\DataGridBundle\Grid\Column\RangeColumn;

class RangeColumnTest extends AbstractTestCase
{
    public function testReturnsEmptyStringForRenderFilter(): void
    {
        $column = new RangeColumn([]);

        $this->assertNotSame('', $column->renderFilter('hash'));
    }
}

