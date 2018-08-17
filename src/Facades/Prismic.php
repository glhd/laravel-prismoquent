<?php

namespace Galahad\Prismoquent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Prismic\Api getFacadeRoot()
 * @method static setComponentPath(string $path)
 * @method static setResolver(\Prismic\LinkResolver $resolver)
 * @method static setDefaultUrl(string $url)
 * @method static registerResolver($type, $resolver)
 * @method static string previewSession($token)
 * @method static \Illuminate\Support\HtmlString asHtml(\Prismic\Fragment\FragmentInterface $fragment)
 * @method static string asText(\Prismic\Fragment\FragmentInterface $fragment)
 * @method static string resolveLink(\Prismic\Fragment\Link\DocumentLink $link)
 * @method static \Galahad\Prismoquent\Prismoquent api()
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
