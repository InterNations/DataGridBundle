<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sorien\DataGridBundle\Tests\Grid\Column;

use InterNations\Component\Testing\AbstractTestCase;
use Sorien\DataGridBundle\Grid\Column\ActionsColumn;

class ActionsColumnTest extends AbstractTestCase
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
        $this->assertInternalType('string', $rendered);
    }
}

