<?php

namespace Galahad\Prismoquent\Support;

use Prismic\Cache\CacheInterface;

class Cache implements CacheInterface
{
	/**
	 * @var \Illuminate\Contracts\Cache\Repository|\Illuminate\Contracts\Cache\Store
	 */
	protected $store;
	
	public function __construct($store)
	{
		$this->store = $store;
	}
	
	public function has($key)
	{
		return null !== $this->store->get($key);
	}
	
	public function get($key)
	{
		return $this->store->get($key);
	}
	
	public function set($key, $value, $ttl = 0)
	{
		$this->store->put($key, $value, $ttl);
	}
	
	public function delete($key)
	{
		$this->store->forget($key);
	}
	
	public function clear()
	{
		$this->store->flush();
	}
}
