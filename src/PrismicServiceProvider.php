<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Http\WebhookController;
use Galahad\Prismoquent\Support\HtmlSerializer;
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
			
			return new Prismoquent(
				$config['services.prismic'],
				$app['prismic.resolver'],
				$app['prismic.serializer'],
				$config['app.url']
			);
		});
		
		$this->app->alias('prismic', Api::class);
		
		$this->app->singleton('prismic.resolver', function(Application $app) {
			return new LinkResolver($app['config']->get('app.url', '/'), $app['url']);
		});
		
		$this->app->alias('prismic.resolver', LinkResolver::class);
		$this->app->alias('prismic.resolver', \Prismic\LinkResolver::class);
		
		$this->app->singleton('prismic.serializer', function(Application $app) {
			return new HtmlSerializer($app['prismic.resolver']);
		});
		
		$this->app->alias('prismic.serializer', HtmlSerializer::class);
		
		$this->app->bind('prismic.controller', function(Application $app) {
			return new WebhookController($app['config']['services.prismic.webhook_secret']);
		});
		
		$this->app->alias('prismic.controller', WebhookController::class);
	}
	
	public function boot()
	{
		Model::setEventDispatcher($this->app['events']);
		Model::setApi($this->app['prismic']);
		
		$controller_enabled = false !== $this->app['config']->get('services.prismic.register_controller');
		
		if ($controller_enabled && !$this->app->routesAreCached()) {
			$this->app['router']->post('/glhd/prismoquent/webhook', WebhookController::class);
		}
	}
}
