<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Support\HtmlSerializer;
use Galahad\Prismoquent\Support\LinkResolver;
use Prismic\Api;
use Prismic\Fragment\FragmentInterface;

/**
 * @mixin \Prismic\Api
 */
class Prismoquent
{
	/**
	 * @var \Prismic\LinkResolver|LinkResolver
	 */
	public $resolver;
	
	/**
	 * @var string
	 */
	public $default_url;
	
	/**
	 * @var \Prismic\Api
	 */
	protected $api;
	
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
		$this->config = $config;
		
		$this->setResolver($resolver);
		$this->setDefaultUrl($default_url);
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
		return $this->api()->previewSession($token, $this->resolver, $this->default_url);
	}
	
	public function asHtml(FragmentInterface $fragment)
	{
		return $fragment->asHtml($this->resolver);
	}
	
	public function asText(FragmentInterface $fragment)
	{
		return $fragment->asText();
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
			
			return Api::get($endpoint, $this->config['api_token'] ?? null);
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
