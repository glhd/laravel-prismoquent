<?php

namespace Galahad\Prismoquent\Support;

use Illuminate\Contracts\Config\Repository;
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
	public function __construct(Repository $config, UrlGenerator $generator)
	{
		$this->generator = $generator;
		$this->app_url = $config->get('app.url', '/');
	}
	
	public function resolve($link)
	{
		if ($resolver = $this->resolvers[$link->type] ?? null) {
			return $resolver($link);
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
