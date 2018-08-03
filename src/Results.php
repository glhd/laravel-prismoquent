<?php

namespace Galahad\Prismoquent;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Prismic\Document;
use Prismic\Response;

/**
 * @method \Prismic\Document|null first(callable $callback = null, $default = null)
 * @method \Prismic\Document[] all()
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
