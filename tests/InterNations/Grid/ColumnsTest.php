<?php
/*
 * This file is part of the DataGridBundle.
 *
 * (c) Lars Strojny <lars.strojny@internations.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace InterNations\DataGridBundle\Tests\Grid;

use PHPUnit\Framework\MockObject\MockObject;
use InterNations\DataGridBundle\Grid\Column\BooleanColumn;
use InterNations\DataGridBundle\Grid\Column\Column;
use InterNations\DataGridBundle\Grid\Column\RangeColumn;
use InterNations\DataGridBundle\Grid\Column\SelectColumn;
use InterNations\DataGridBundle\Grid\Column\TextColumn;
use InterNations\DataGridBundle\Grid\Columns;
use PHPUnit\Framework\TestCase;

class ColumnsTest extends TestCase
{
    /** @var Columns */
    private $columns;

    /** @var Column|MockObject */
    private $column;

    public function setUp(): void
    {
        $this->column = $this->createConfiguredMock(Column::class, ['getId' => 'column']);
        $this->columns = new Columns();
    }

    public function testAddingColumns(): void
    {
        $textColumn = new TextColumn(['id' => 'text']);
        $rangeColumn = new RangeColumn(['id' => 'range']);
        $selectColumn = new SelectColumn(['id' => 'select']);
        $booleanColumn = new BooleanColumn(['id' => 'boolean']);

        $this->assertCount(0, $this->columns);

        $this->columns->addColumn($textColumn);
        $this->assertCount(1, $this->columns);

        $this->columns->addColumn($rangeColumn);
        $this->assertCount(2, $this->columns);
        $this->assertSame([$textColumn, $rangeColumn], iterator_to_array($this->columns->getIterator()));

        $this->columns->addColumn($selectColumn, 0);
        $this->assertCount(3, $this->columns);
        $this->assertSame(
            [$selectColumn, $textColumn, $rangeColumn],
            iterator_to_array($this->columns->getIterator())
        );

        $this->columns->addColumn($booleanColumn, 4);
        $this->assertCount(4, $this->columns);
        $this->assertSame(
            [$selectColumn, $textColumn, $rangeColumn, $booleanColumn],
            iterator_to_array($this->columns->getIterator())
        );
    }

    public function testAccessingColumns(): void
    {
        $this->assertFalse($this->columns->hasColumnById('column'));
        $this->assertNull($this->columns->getColumnByIdOrNull('column'));
        $this->columns->addColumn($this->column);
        $this->assertTrue($this->columns->hasColumnById('column'));
        $this->assertSame($this->column, $this->columns->getColumnById('column'));
        $this->assertSame($this->column, $this->columns->getColumnByIdOrNull('column'));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Column with ID "undefinedColumn" does not exist');
        $this->assertSame($this->column, $this->columns->getColumnById('undefinedColumn'));
    }

    public function testGetHash(): void
    {
        $this->assertSame(
            'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            $this->columns->getHash()
        );

        $this->columns
            ->addColumn(new TextColumn(['id' => 'text']))
            ->addColumn(new RangeColumn(['id' => 'range']));
        $this->assertSame(
            'dd67e36636c920e13f83df1df3b1882c93c7d02a1fa04eb8a00a9aac6abe0c6e',
            $this->columns->getHash()
        );
    }
}
