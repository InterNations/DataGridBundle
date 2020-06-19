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

use InterNations\DataGridBundle\Grid\Column\ActionsColumn;
use PHPUnit\Framework\TestCase;

class ActionsColumnTest extends TestCase
{
    public function testActionsColumnWithSubmitOnChangeReturnsEmptyStringForRenderFilter(): void
    {
        $column = new ActionsColumn('actions', 'Actions');
        $column->setSubmitOnChange(true);

        $rendered = $column->renderFilter('hash');
        $this->assertSame('', $rendered);
    }

    public function testActionsColumnWithSubmitOnChangeReturnsStringForRenderFilter(): void
    {
        $column = new ActionsColumn('actions', 'Actions');
        $column->setSubmitOnChange(false);

        $rendered = $column->renderFilter('hash');
        $this->assertNotSame('', $rendered);
        $this->assertIsString($rendered);
    }
}

