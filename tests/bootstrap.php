<?php

namespace Oblik\Walker;

use Kirby\Cms\App;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
	public function __construct()
	{
		parent::__construct(...func_get_args());
	}

	public function setUp(): void
	{
		new App();
	}

	public function tearDown(): void
	{
		App::destroy();
	}
}
