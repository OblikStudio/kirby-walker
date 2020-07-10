<?php

namespace Oblik\Outsource;

use ReflectionClass;
use Kirby\Cms\App;
use Kirby\Cms\Dir;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected $app;
    protected $fixtures;
    protected $baseFixtures;

    public function __construct()
    {
        parent::__construct(...func_get_args());

        $ref = new ReflectionClass($this);
        $dir = dirname($ref->getFileName());

        $this->baseFixtures = $dir . DS . 'fixtures';
        $this->fixtures = $this->baseFixtures . DS . $ref->getShortName();
    }

    public function setUp(): void
    {
        $this->app = new App([
            'roots' => [
                'index' => $this->fixtures
            ]
        ]);

        $this->app->impersonate('kirby');

        Dir::make($this->fixtures);
    }

    public function tearDown(): void
    {
        App::destroy();

        Dir::remove($this->baseFixtures);
    }
}

new App();
