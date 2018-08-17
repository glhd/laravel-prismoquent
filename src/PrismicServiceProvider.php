<?php

namespace Galahad\Prismoquent;

use Galahad\Prismoquent\Http\WebhookController;
use Galahad\Prismoquent\Support\HtmlSerializer;
use Galahad\Prismoquent\Support\LinkResolver;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Prismic\Api;
use Prismic\Fragment\SliceInterface;

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
				$app['config']->get('app.url', '/')
			);
		});
		
		$this->app->alias('prismic', Prismoquent::class);
		
		$this->app->singleton('prismic.resolver', function(Container $app) {
			return new LinkResolver($app['config']->get('app.url', '/'), $app['url']);
		});
		
		$this->app->alias('prismic.resolver', LinkResolver::class);
		
		// $this->app->bind('prismic.controller', function(Container $app) {
		// 	return new WebhookController($app['config']['services.prismic.webhook_secret']);
		// });
		//
		// $this->app->alias('prismic.controller', WebhookController::class);
	}
	
	public function boot()
	{
		// /** @var \Illuminate\Contracts\Config\Repository $config */
		// $config = $this->app['config'];
		
		Model::setEventDispatcher($this->app['events']);
		Model::setApi($this->app['prismic']);
		
		$this->registerBladeDirectives();
		
		// if ($this->app instanceof \Illuminate\Foundation\Application) {
		// 	$controller_enabled = false !== $config->get('services.prismic.register_controller');
		//
		// 	if ($controller_enabled && !$this->app->routesAreCached()) {
		// 		$this->app['router']->post('/glhd/prismoquent/webhook', WebhookController::class);
		// 	}
		// }
	}
	
	protected function registerBladeDirectives()
	{
		/** @var BladeCompiler $compiler */
		$compiler = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();
		
		$compiler->directive('slice', function($slice) {
			return "<?php \$__env->startComponent({$slice}->getSliceType(), ['slice' => {$slice}]); echo \$__env->renderComponent(); ?>";
		});
		
		$compiler->directive('slices', function($slices) {
			return "<?php foreach({$slices}->getSlices() as \$__prismoquent_slice): \$__env->startComponent(\$__prismoquent_slice->getSliceType(), ['slice' => \$__prismoquent_slice]); echo \$__env->renderComponent(); endforeach; ?>";
		});
	}
}
