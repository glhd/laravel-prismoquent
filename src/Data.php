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
	protected $_prismoquent_model;
	
	/**
	 * The raw value of the data at this page
	 *
	 * @var mixed
	 */
	protected $_prismoquent_raw;
	
	/**
	 * @var string
	 */
	protected $_prismoquent_prefix;
	
	/**
	 * Constructor
	 *
	 * @param \Galahad\Prismoquent\Model $model
	 * @param string $prefix
	 */
	public function __construct(Model $model, $value, $prefix = 'data')
	{
		$this->_prismoquent_model = $model;
		$this->_prismoquent_raw = $value;
		$this->_prismoquent_prefix = $prefix;
	}
	
	public function model() : Model
	{
		return $this->_prismoquent_model;
	}
	
	public function raw()
	{
		return $this->_prismoquent_raw;
	}
	
	public function prefix() : string
	{
		return $this->_prismoquent_prefix;
	}
	
	/**
	 * Get data
	 *
	 * @param $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		return $this->_prismoquent_model->getAttribute("{$this->_prismoquent_prefix}.{$name}");
	}
	
	/**
	 * Set data
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set($name, $value)
	{
		$this->_prismoquent_model->offsetSet($name, $value);
	}
	
	/**
	 * Is data set
	 *
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
	{
		return $this->_prismoquent_model->offsetExists($name);
	}
	
	/**
	 * Does an array offset exist in data
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return $this->_prismoquent_model->offsetExists("{$this->_prismoquent_prefix}.{$offset}");
	}
	
	/**
	 * Get data
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->_prismoquent_model->offsetGet("{$this->_prismoquent_prefix}.{$offset}");
	}
	
	/**
	 * Set data
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value)
	{
		$this->_prismoquent_model->offsetSet("{$this->_prismoquent_prefix}.{$offset}", $value);
	}
	
	/**
	 * Unset data
	 *
	 * @param mixed $offset
	 */
	public function offsetUnset($offset)
	{
		$this->_prismoquent_model->offsetUnset("{$this->_prismoquent_prefix}.{$offset}");
	}
}
