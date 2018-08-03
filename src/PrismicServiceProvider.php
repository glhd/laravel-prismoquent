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
			return new Prismoquent($config['services.prismic'], $app['prismic.resolver'], $config['app.url']);
		});
		
		$this->app->alias('prismic', Api::class);
		
		$this->app->singleton('prismic.resolver', function(Application $app) {
			return new LinkResolver($app['config']->get('app.url', '/'), $app['url']);
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
