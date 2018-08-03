<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Support\LinkResolver;
use Prismic\Api;

/**
 * @mixin \Prismic\Api
 */
class Prismoquent
{
	/**
	 * @var \Prismic\Api
	 */
	protected $api;
	
	/**
	 * @var \Prismic\LinkResolver|LinkResolver
	 */
	public $resolver;
	
	/**
	 * @var string
	 */
	public $default_url;
	
	/**
	 * @var array
	 */
	protected $config;
	
	/**
	 * Constructor
	 *
	 * @param array $config
	 * @param \Galahad\Prismoquent\Support\LinkResolver $resolver
	 * @param string $default_url
	 */
	public function __construct(array $config, LinkResolver $resolver, string $default_url)
	{
		$this->setResolver($resolver);
		$this->setDefaultUrl($default_url);
		$this->config = $config;
	}
	
	public function setResolver(\Prismic\LinkResolver $resolver) : self
	{
		$this->resolver = $resolver;
		
		return $this;
	}
	
	public function setDefaultUrl(string $url) : self
	{
		$this->default_url = $url;
		
		return $this;
	}
	
	public function registerResolver($type, $resolver) : LinkResolver
	{
		return $this->resolver->registerResolver($type, $resolver);
	}
	
	public function previewSession($token) : string
	{
		return $this->api->previewSession($token, $this->resolver, $this->default_url);
	}
	
	/**
	 * @return \Prismic\Api
	 *
	 * @throws \Galahad\Prismoquent\Exception
	 */
	public function api()
	{
		if (null === $this->api) {
			if (!$endpoint = $this->config['endpoint'] ?? null) {
				throw new Exception('services.prismic.endpoint is not set');
			}
			
			if (!$token = $this->config['api_token'] ?? null) {
				throw new Exception('services.prismic.api_token is not set');
			}
			
			return Api::get($endpoint, $token);
		}
		
		return $this->api;
	}
	
	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 *
	 * @throws \Galahad\Prismoquent\Exception
	 */
	public function __call($name, $arguments)
	{
		return $this->api()->$name(...$arguments);
	}
}
