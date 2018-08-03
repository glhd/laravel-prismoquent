<?php

namespace Galahad\Prismoquent;

use ArrayAccess;

class Data implements ArrayAccess
{
	public $model;
	
	public function __construct(Model $model)
	{
		$this->model = $model;
	}
	
	public function __get($name)
	{
		return $this->model->getAttribute("data.{$name}");
	}
	
	public function __set($name, $value)
	{
		$this->model->offsetSet($name, $value);
	}
	
	public function __isset($name)
	{
		return $this->model->offsetExists($name);
	}
	
	public function offsetExists($offset)
	{
		return $this->model->offsetExists("data.{$offset}");
	}
	
	public function offsetGet($offset)
	{
		return $this->model->offsetGet("data.{$offset}");
	}
	
	public function offsetSet($offset, $value)
	{
		$this->model->offsetSet("data.{$offset}", $value);
	}
	
	public function offsetUnset($offset)
	{
		$this->model->offsetUnset("data.{$offset}");
	}
}
