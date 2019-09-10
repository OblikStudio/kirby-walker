<?php

namespace Oblik\Outsource;

use PHPUnit\Framework\TestCase;
use Kirby\Cms\Pages;

final class DiffTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $data = json_decode(file_get_contents(realpath(__DIR__ . '/../fixtures/diff-actual.json')), true);
        $snapshot = json_decode(file_get_contents(realpath(__DIR__ . '/../fixtures/diff-snapshot.json')), true);

        $diff = new Diff();
        $result = $diff->process($data, $snapshot);
        var_export($result);
    }
}
