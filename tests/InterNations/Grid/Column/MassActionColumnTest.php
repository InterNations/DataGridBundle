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
use InterNations\DataGridBundle\Grid\Column\ActionsColumn;
use InterNations\DataGridBundle\Grid\Column\MassActionColumn;

class MassActionColumnTest extends AbstractTestCase
{
    public function testMassActionsColumnRendersFilter(): void
    {
        $column = new MassActionColumn(MassActionColumn::ID);

        $rendered = $column->renderFilter('hash');
        $this->assertNotSame('', $rendered);
        $this->assertInternalType('string', $rendered);
    }
}

