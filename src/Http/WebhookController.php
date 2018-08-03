<?php

namespace Galahad\Prismoquent\Http;

use Galahad\Prismoquent\Events\ApiUpdate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebhookController extends Controller
{
	/**
	 * Secret used to validate request data
	 *
	 * @var string
	 */
	protected $prismic_secret;
	
	public function __construct(string $prismic_secret)
	{
		$this->prismic_secret = $prismic_secret;
	}
	
	public function __invoke(Request $request, Dispatcher $events)
	{
		$payload = $request->json();
		
		if ($payload->secret !== $this->prismic_secret) {
			throw new NotFoundHttpException();
		}
		
		$events->dispatch(new ApiUpdate($payload));
		
		return new Response('OK', 200, ['Content-Type' => 'text/plain']);
	}
}
