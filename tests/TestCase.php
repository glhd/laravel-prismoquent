<?php

namespace Galahad\Prismoquent\Tests;

use Galahad\Prismoquent\PrismicServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraCase;

abstract class TestCase extends OrchestraCase
{
	protected function getPackageProviders($app)
	{
		return [
			PrismicServiceProvider::class
		];
	}
}
