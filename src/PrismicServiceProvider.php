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
			
			$endpoint = $config->get('services.prismic.endpoint');
			$token = $config->get('services.prismic.api_token');
			
			return Api::get("$endpoint/api/v2", $token);
		});
		
		$this->app->alias('prismic', Api::class);
	}
	
	public function boot()
	{
		Model::setApi($this->app['prismic']);
		Model::setEventDispatcher($this->app['events']);
	}
}
