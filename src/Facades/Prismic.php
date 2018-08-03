<?php

namespace Galahad\Prismoquent\Facades;

use Illuminate\Support\Facades\Facade;
use Prismic\LinkResolver;

/**
 * @method static \Prismic\Api getFacadeRoot()
 */
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
