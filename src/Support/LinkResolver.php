<?php

namespace Galahad\Prismoquent\Support;

use Illuminate\Contracts\Routing\UrlGenerator;

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
	
	protected $resolvers = [];
	
	/**
	 * LinkResolver constructor.
	 *
	 * @param \Illuminate\Contracts\Config\Repository $config
	 * @param \Illuminate\Contracts\Routing\UrlGenerator $generator
	 */
	public function __construct(string $app_url, $generator)
	{
		$this->generator = $generator;
		$this->app_url = $app_url;
	}
	
	public function resolve($link)
	{
		if ($resolver = $this->resolvers[$link->type] ?? null) {
			return $resolver($link);
		}
		
		// Allow wildcard resolvers
		if (isset($this->resolvers['*'])) {
			return $this->resolvers['*']($link);
		}
		
		return $this->app_url;
	}
	
	public function registerResolver($type, $resolver) : self
	{
		if (is_string($resolver)) {
			$route_name = $resolver;
			$resolver = function($link) use ($route_name) {
				return $this->generator->route($route_name, (array) $link);
			};
		}
		
		if (!is_callable($resolver)) {
			throw new \InvalidArgumentException('A link resolver must be a route name or callable');
		}
		
		$this->resolvers[$type] = $resolver;
		
		return $this;
	}
}
