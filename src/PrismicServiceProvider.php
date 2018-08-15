<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Http\WebhookController;
use Galahad\Prismoquent\Support\HtmlSerializer;
use Galahad\Prismoquent\Support\LinkResolver;
use Illuminate\Contracts\Container\Container;
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
		$this->app->singleton('prismic', function(Container $app) {
			$config = $app['config'];
			
			return new Prismoquent(
				$config->get('services.prismic', [
					'endpoint' => env('PRISMIC_ENDPOINT'),
				]),
				$app['prismic.resolver'],
				$app['prismic.serializer'],
				$config->get('app.url', '/')
			);
		});
		
		$this->app->alias('prismic', Api::class);
		
		$this->app->singleton('prismic.resolver', function(Container $app) {
			return new LinkResolver($app['config']->get('app.url', '/'), $app['url']);
		});
		
		$this->app->alias('prismic.resolver', LinkResolver::class);
		
		$this->app->singleton('prismic.serializer', function(Container $app) {
			return new HtmlSerializer($app['prismic.resolver']);
		});
		
		$this->app->alias('prismic.serializer', HtmlSerializer::class);
		
		$this->app->bind('prismic.controller', function(Container $app) {
			return new WebhookController($app['config']['services.prismic.webhook_secret']);
		});
		
		$this->app->alias('prismic.controller', WebhookController::class);
	}
	
	public function boot()
	{
		/** @var \Illuminate\Contracts\Config\Repository $config */
		$config = $this->app['config'];
		
		Model::setEventDispatcher($this->app['events']);
		Model::setApi($this->app['prismic']);
		
		if ($this->app instanceof \Illuminate\Foundation\Application) {
			$controller_enabled = false !== $config->get('services.prismic.register_controller');
			
			if ($controller_enabled && !$this->app->routesAreCached()) {
				$this->app['router']->post('/glhd/prismoquent/webhook', WebhookController::class);
			}
		}
	}
}
