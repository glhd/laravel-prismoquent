<?php

namespace Galahad\Prismoquent;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Prismic\Document;
use stdClass;

/**
 * @method \Galahad\Prismoquent\Model|null first(callable $callback = null, $default = null)
 * @method \Galahad\Prismoquent\Model[] all()
 */
class Results extends LengthAwarePaginator
{
	/**
	 * @var Response
	 */
	public $response;
	
	/**
	 * Constructor
	 *
	 * @param \Galahad\Prismoquent\Model $model
	 * @param Response $response
	 */
	public function __construct(Model $model, $response)
	{
		$items = Collection::make($response->results)
			->map(function($document) use ($model) {
				return $model->newInstance($document);
			});
		
		parent::__construct(
			$items,
			$response->total_results_size,
			$response->results_per_page,
			$response->page
		);
		
		$this->response = $response;
	}
}
