<?php

namespace Galahad\Prismoquent\Facades;

use Illuminate\Support\Facades\Facade;
use Prismic\LinkResolver;

/**
 * @method static \Prismic\Api getFacadeRoot()
 */
class Prismic extends Facade
{
	public static function registerResolver($type, $resolver)
	{
		return static::$app['prismic.resolver']->registerResolver($type, $resolver);
	}
	
	public static function previewSession($token, LinkResolver $linkResolver = null, $defaultUrl = null)
	{
		if (null === $linkResolver) {
			$linkResolver = static::$app['prismic.resolver'];
		}
		
		if (null === $defaultUrl) {
			$defaultUrl = static::$app['config']->get('app.url', '/');
		}
		
		return static::getFacadeRoot()->previewSession($token, $linkResolver, $defaultUrl);
	}
	
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
