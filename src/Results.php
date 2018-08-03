<?php

namespace Galahad\Prismoquent;

use Illuminate\Pagination\LengthAwarePaginator;
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
	public function __construct(Response $response)
	{
		parent::__construct(
			$response->getResults(),
			$response->getTotalResultsSize(),
			$response->getResultsPerPage(),
			$response->getPage()
		);
		
		$this->response = $response;
	}
}
