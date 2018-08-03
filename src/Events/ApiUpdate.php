<?php

namespace Galahad\Prismoquent\Events;

class ApiUpdate
{
	public $payload;
	
	public function __construct($payload)
	{
		$this->payload = $payload;
	}
	
	public function __get($name)
	{
		return $this->payload->$name;
	}
	
	public function __isset($name) : bool
	{
		return isset($this->payload->$name);
	}
}
