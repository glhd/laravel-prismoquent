<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Support\Cache;
use Galahad\Prismoquent\Support\Http\WebhookController;
use Galahad\Prismoquent\Support\LinkResolver;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

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
			return new Prismoquent(
				$app['config']->get('services.prismic', [
					'endpoint' => env('PRISMIC_ENDPOINT'),
				]),
				$app['prismic.resolver'],
				$app['prismic.cache'],
				$app['config']->get('app.url', '/')
			);
		});
		
		$this->app->alias('prismic', Prismoquent::class);
		
		$this->app->singleton('prismic.resolver', function(Container $app) {
			return new LinkResolver($app['config']->get('app.url', '/'), $app['url']);
		});
		
		$this->app->alias('prismic.resolver', LinkResolver::class);
		
		$this->app->bind('prismic.controller', function(Container $app) {
			return new WebhookController($app['config']->get('services.prismic.webhook_secret'));
		});
		
		$this->app->alias('prismic.controller', WebhookController::class);
		
		$this->app->bind('prismic.cache', function(Container $app) {
			$store = $app['cache']->getStore();
			
			$repository = $store instanceof TaggableStore
				? $store->tags('prismoquent')
				: $store;
			
			return new Cache($repository);
		});
		
		$this->app->alias('prismic.cache', Cache::class);
	}
	
	public function boot()
	{
		Model::setEventDispatcher($this->app['events']);
		Model::setApi($this->app['prismic']);
		
		$this->registerRoutes();
		$this->registerBladeDirectives();
	}
	
	protected function registerRoutes()
	{
		$controller_enabled = (false !== $this->app['config']->get('services.prismic.register_controller'));
		
		if ($controller_enabled && !$this->app->routesAreCached()) {
			$this->app['router']->post('/glhd/prismoquent/webhook', WebhookController::class);
		}
	}
	
	protected function registerBladeDirectives()
	{
		/** @var BladeCompiler $compiler */
		$compiler = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();
		
		$compiler->directive('slice', function($slice) {
			return "<?php \\Galahad\\Prismoquent\\Facades\\Prismic::sliceComponent(\$__env, {$slice}); ?>";
		});
		
		$compiler->directive('slices', function($slices) {
			return "<?php foreach({$slices}->getSlices() as \$__prismoquent_slice): \\Galahad\\Prismoquent\\Facades\\Prismic::sliceComponent(\$__env, \$__prismoquent_slice); endforeach; ?>";
		});
		
		$compiler->directive('asHtml', function($fragment) {
			return "<?php echo \\Galahad\\Prismoquent\\Facades\\Prismic::asHtml({$fragment}); ?>";
		});
		
		$compiler->directive('asText', function($fragment) {
			return "<?php echo \\Galahad\\Prismoquent\\Facades\\Prismic::asText({$fragment}); ?>";
		});
		
		$compiler->directive('resolveLink', function($fragment) {
			return "<?php echo \\Galahad\\Prismoquent\\Facades\\Prismic::resolveLink({$fragment}); ?>";
		});
	}
}
