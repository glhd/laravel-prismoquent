<?php

namespace Galahad\Prismoquent;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Prismic\Document;
use Prismic\Response;

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
	public function __construct(Model $model, Response $response)
	{
		$items = Collection::make($response->getResults())
			->map(function(Document $document) use ($model) {
				return $model->newInstance($document);
			});
		
		parent::__construct(
			$items,
			$response->getTotalResultsSize(),
			$response->getResultsPerPage(),
			$response->getPage()
		);
		
		$this->response = $response;
	}
}
