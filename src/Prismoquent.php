<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Support\HtmlSerializer;
use Galahad\Prismoquent\Support\LinkResolver;
use Illuminate\Support\HtmlString;
use Illuminate\View\Factory;
use Prismic\Api;
use Prismic\Fragment\CompositeSlice;
use Prismic\Fragment\FragmentInterface;
use Prismic\Fragment\Link\DocumentLink;
use Prismic\Fragment\SliceInterface;

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
	 * @var string
	 */
	protected $componentPath = 'slices';
	
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
	
	public function setComponentPath(string $path) : self
	{
		$this->componentPath = str_replace(DIRECTORY_SEPARATOR, '.', $path);
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
		return new HtmlString($fragment->asHtml($this->resolver));
	}
	
	public function asText(FragmentInterface $fragment)
	{
		return $fragment->asText();
	}
	
	public function resolveLink(DocumentLink $link)
	{
		return $this->resolver->resolveLink($link);
	}
	
	public function sliceComponent(Factory $factory, SliceInterface $slice)
	{
		$type = $slice->getSliceType();
		$componentPath = empty($this->componentPath)
			? $type
			: "{$this->componentPath}.{$type}";
		
		$data = [
			'slice' => $slice,
			'primary' => $slice instanceof CompositeSlice
				? $slice->getPrimary()
				: $slice,
			'fragments' => new \stdClass(),
		];
		
		if ($slice->isComposite()) {
			foreach ($slice->getPrimary()->getFragments() as $key => $fragment) {
				$data['fragments']->$key = $fragment;
				
				if (!isset($data[$key])) {
					$data[$key] = $fragment;
				}
			}
		}
			
		$factory->startComponent($componentPath, $data);
		echo $this->asHtml($slice);
		echo $factory->renderComponent();
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
