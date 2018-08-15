<?php

namespace Galahad\Prismoquent\Tests;

use Galahad\Prismoquent\Facades\Prismic;
use Galahad\Prismoquent\PrismicServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraCase;

abstract class TestCase extends OrchestraCase
{
	protected function getPackageProviders($app) : array
	{
		return [
			PrismicServiceProvider::class,
		];
	}
	
	protected function getPackageAliases($app) : array
	{
		return [
			'Prismic' => Prismic::class,
		];
	}
}
