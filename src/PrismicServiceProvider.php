<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Support\LinkResolver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Prismic\Api;

class PrismicServiceProvider extends ServiceProvider
{
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('prismic', function(Application $app) {
			$config = $app['config'];
			
			if (!$endpoint = $config['services.prismic.endpoint']) {
				throw new Exception('services.prismic.endpoint is not set');
			}
			
			if (!$token = $config['services.prismic.api_token']) {
				throw new Exception('services.prismic.api_token is not set');
			}
			
			return Api::get($endpoint, $token);
		});
		
		$this->app->alias('prismic', Api::class);
		
		$this->app->singleton('prismic.resolver', function(Application $app) {
			return new LinkResolver($app['config'], $app['url']);
		});
		
		$this->app->alias('prismic.resolver', LinkResolver::class);
		$this->app->alias('prismic.resolver', \Prismic\LinkResolver::class);
	}
	
	public function boot()
	{
		Model::setEventDispatcher($this->app['events']);
		
		if ($this->app['config']->has('services.prismic.endpoint')) {
			Model::setApi($this->app['prismic']);
		}
	}
}
