<?php
namespace InterNations\DataGridBundle\Tests\Source;

use InterNations\DataGridBundle\Grid\Source\Entity;
use PHPUnit\Framework\TestCase;

class EntitySourceTest extends TestCase
{
    public function testAliasConstants(): void
    {
        self::assertSame('_a', Entity::TABLE_ALIAS);
        self::assertSame('__count', Entity::COUNT_ALIAS);
    }
}
