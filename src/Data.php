<?php

namespace Galahad\Prismoquent;

use ArrayAccess;

/**
 * This object exists to proxy data-> calls back to model as data. calls
 * so that mutators can be applied.
 */
class Data implements ArrayAccess
{
	/**
	 * @var \Galahad\Prismoquent\Model
	 */
	public $model;
	
	/**
	 * Constructor
	 *
	 * @param \Galahad\Prismoquent\Model $model
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
	}
	
	/**
	 * Get data
	 *
	 * @param $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		return $this->model->getAttribute("data.{$name}");
	}
	
	/**
	 * Set data
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->model->offsetSet($name, $value);
	}
	
	/**
	 * Is data set
	 *
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->model->offsetExists($name);
	}
	
	/**
	 * Does an array offset exist in data
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->model->offsetExists("data.{$offset}");
	}
	
	/**
	 * Get data
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->model->offsetGet("data.{$offset}");
	}
	
	/**
	 * Set data
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->model->offsetSet("data.{$offset}", $value);
	}
	
	/**
	 * Unset data
	 *
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		$this->model->offsetUnset("data.{$offset}");
	}
}
