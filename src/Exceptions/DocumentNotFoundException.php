<?php

namespace Galahad\Prismoquent\Exceptions;

use Illuminate\Support\Arr;
use RuntimeException;

class DocumentNotFoundException extends RuntimeException
{
	/**
	 * Name of the affected Prismoquent document.
	 *
	 * @var string
	 */
	protected $document;
	
	/**
	 * The affected model IDs.
	 *
	 * @var int|array
	 */
	protected $ids;
	
	/**
	 * Set the affected Eloquent model and instance ids.
	 *
	 * @param  string  $model
	 * @param  int|array  $ids
	 * @return $this
	 */
	public function setDocument($model, $ids = [])
	{
		$this->document = $model;
		$this->ids = Arr::wrap($ids);
		
		$this->message = "No query results for document [{$model}]";
		
		if (count($this->ids) > 0) {
			$this->message .= ' '.implode(', ', $this->ids);
		} else {
			$this->message .= '.';
		}
		
		return $this;
	}
	
	/**
	 * Get the affected Eloquent model.
	 *
	 * @return string
	 */
	public function getDocument()
	{
		return $this->document;
	}
	
	/**
	 * Get the affected Eloquent model IDs.
	 *
	 * @return int|array
	 */
	public function getIds()
	{
		return $this->ids;
	}
}
