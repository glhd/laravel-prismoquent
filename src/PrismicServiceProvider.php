<?php

namespace Galahad\Prismoquent;

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
			
			// FIXME: These will throw after compose install
			
			if (!$endpoint = $config->get('services.prismic.endpoint')) {
				throw new Exception('services.prismic.endpoint is not set');
			}
			
			if (!$token = $config->get('services.prismic.api_token')) {
				throw new Exception('services.prismic.api_token is not set');
			}
			
			return Api::get($endpoint, $token);
		});
		
		$this->app->alias('prismic', Api::class);
	}
	
	public function boot()
	{
		Model::setApi($this->app['prismic']);
		Model::setEventDispatcher($this->app['events']);
	}
}
