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

use InterNations\DataGridBundle\Grid\Column\TextColumn;
use PHPUnit\Framework\TestCase;

class TextColumnTest extends TestCase
{
    public function testReturnsEmptyStringForRenderFilter(): void
    {
        $column = new TextColumn([]);

        $this->assertNotSame('', $column->renderFilter('hash'));
    }
}

