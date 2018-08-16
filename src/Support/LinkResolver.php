<?php

namespace Galahad\Prismoquent\Support;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Support\Str;
use Prismic\Fragment\Link\DocumentLink;

class LinkResolver extends \Prismic\LinkResolver
{
	/**
	 * @var \Illuminate\Contracts\Routing\UrlGenerator
	 */
	protected $generator;
	
	/**
	 * @var string
	 */
	protected $app_url;
	
	/**
	 * @var callable[]
	 */
	protected $resolvers = [];
	
	/**
	 * LinkResolver constructor.
	 *
	 * @param string $app_url
	 * @param \Illuminate\Contracts\Routing\UrlGenerator $generator
	 */
	public function __construct(string $app_url, UrlGenerator $generator)
	{
		$this->generator = $generator;
		$this->app_url = $app_url;
	}
	
	/**
	 * @param \Prismic\Fragment\Link\DocumentLink|\Prismic\Fragment\Link\LinkInterface $link
	 * @return null|string
	 */
	public function resolve($link) : string
	{
		// We really only care about DocumentLink objects
		if ($link instanceof DocumentLink) {
			$type = $link->getType();
			
			if ($resolver = $this->resolvers[$type] ?? null) {
				return $resolver($link);
			}
			
			// Check for a route that matches the type name
			// Eg. for a content type called "Page" it would use "pages.show"
			try {
				$plural = Str::plural($type);
				return $this->generator->route("{$plural}.show", $link->getUid());
			} catch (\InvalidArgumentException $e) {
			}
			
			// Allow wildcard resolvers
			if (isset($this->resolvers['*'])) {
				return $this->resolvers['*']($link);
			}
		}
		
		return $this->app_url;
	}
	
	/**
	 * @param string $type
	 * @param string|callable $resolver
	 * @return \Galahad\Prismoquent\Support\LinkResolver
	 */
	public function registerResolver($type, $resolver) : self
	{
		if (is_string($resolver)) {
			$route_name = $resolver;
			$resolver = function($link) use ($route_name) {
				if ($link instanceof DocumentLink) {
					return $this->generator->route($route_name, $link->getType());
				}
				throw new \InvalidArgumentException('The built-in link resolver can only resolve document links.');
			};
		}
		
		if (!is_callable($resolver)) {
			throw new \InvalidArgumentException('A link resolver must be a route name or callable');
		}
		
		$this->resolvers[$type] = $resolver;
		
		return $this;
	}
}
