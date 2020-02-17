<?php

namespace Oblik\Outsource;

use Oblik\Outsource\Util\Diff;

use PHPUnit\Framework\TestCase;

final class DiffTest extends TestCase
{
    public static $data;

    public static function setUpBeforeClass(): void
    {
        $data = json_decode(file_get_contents(realpath(__DIR__ . '/../fixtures/diff-actual.json')), true);
        $snapshot = json_decode(file_get_contents(realpath(__DIR__ . '/../fixtures/diff-snapshot.json')), true);

        self::$data = Diff::process($data, $snapshot);
    }

    public function testSameContentRemoved()
    {
        $this->assertArrayNotHasKey('text', self::$data);
    }

    public function testArrayHasOnlyChangedValue()
    {
        $this->assertArrayHasKey('text', self::$data['yamlfield']);
        $this->assertArrayNotHasKey('id', self::$data['yamlfield']);
    }

    public function testArrayIndexPreserved()
    {
        $this->assertArrayHasKey(1, self::$data['struct']);
        $this->assertArrayNotHasKey(0, self::$data['struct']);
    }

    public function testKeyedArray()
    {
        $ids = array_column(self::$data['synced'], 'id');
        $this->assertEquals(['b'], $ids);
    }

    public function testEntryIdPreserved()
    {
        $this->assertEquals('b-b', self::$data['synced'][1]['nested'][2]['id']);
    }
}
