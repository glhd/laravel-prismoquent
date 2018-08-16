<?php

namespace Galahad\Prismoquent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Prismic\Api getFacadeRoot()
 * @mixin \Galahad\Prismoquent\Prismoquent
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
