<?php

namespace Oblik\Walker\Util;

use PHPUnit\Framework\TestCase;

final class DiffTest extends TestCase
{
    public function testReturnsOnlyChangedValue()
    {
        $result = Diff::process(
            ['foo' => 'a', 'bar' => 'b'],
            ['foo' => 'a', 'bar' => 'a']
        );

        $this->assertArrayNotHasKey('foo', $result);
        $this->assertArrayHasKey('bar', $result);
    }

    public function testDoesNotIncludeSnapshotValue()
    {
        $result = Diff::process(
            ['foo' => 'b'],
            ['foo' => 'a', 'bar' => 'a']
        );

        $this->assertArrayNotHasKey('bar', $result);
    }

    public function testArrayIndexPreserved()
    {
        $result = Diff::process(
            [['foo' => 'a'], ['foo' => 'b']],
            [['foo' => 'a'], ['foo' => 'a']]
        );

        $this->assertArrayHasKey(1, $result);
        $this->assertArrayNotHasKey(0, $result);
    }

    public function testRespectsIdInArray()
    {
        $result = Diff::process(
            [['id' => 'a', 'foo' => 'a'], ['id' => 'b', 'foo' => 'b']],
            [['id' => 'b', 'foo' => 'b'], ['id' => 'a', 'foo' => 'a']]
        );

        $this->assertEquals(null, $result);
    }

    public function testArrayIdPreserved()
    {
        $result = Diff::process(
            [['id' => 'b', 'foo' => 'b']],
            [['id' => 'a'], ['id' => 'b', 'foo' => 'a']]
        );

        $this->assertEquals('b', $result[0]['id']);
    }
}
