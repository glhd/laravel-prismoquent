<?php

namespace Galahad\Prismoquent\Facades;

use Illuminate\Support\Facades\Facade;

class Prismic extends Facade
{
	/**
	 * Get the registered name of the component
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'prismic';
	}
}
