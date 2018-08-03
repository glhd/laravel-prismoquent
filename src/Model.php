<?php

namespace Galahad\Prismoquent;

use ArrayAccess;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Str;
use JsonSerializable;
use Prismic\Api;
use Prismic\Document;
use RuntimeException;

abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, UrlRoutable
{
	use HasAttributes, HasEvents, HasTimestamps, HidesAttributes, GuardsAttributes;
	
	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected static $dispatcher;
	
	/**
	 * @var Api
	 */
	protected static $api;
	
	/**
	 * The original Prismic document
	 *
	 * @var Document
	 */
	public $document;
	
	/**
	 * The API ID of the content type this represents
	 *
	 * @var string
	 */
	protected $api_id;
	
	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $perPage = 20;
	
	/**
	 * Create a new Prismoquent model instance.
	 *
	 * @param Document $document
	 */
	public function __construct(Document $document = null)
	{
		if ($document) {
			$this->setDocument($document);
		}
	}
	
	/**
	 * Set the Prismic API instance
	 *
	 * @param Api $api
	 */
	public static function setApi(Api $api) : void
	{
		static::$api = $api;
	}
	
	/**
	 * Begin querying the model.
	 *
	 * @return \Galahad\Prismoquent\Builder
	 */
	public static function query() : Builder
	{
		return (new static())->newQuery();
	}
	
	/**
	 * Get all of the documents
	 *
	 * @return \Galahad\Prismoquent\Results
	 */
	public static function all() : Results
	{
		return static::query()->get();
	}
	
	/**
	 * Handle dynamic static method calls into the method.
	 *
	 * @param  string $method
	 * @param  array $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		return (new static())->$method(...$parameters);
	}
	
	/**
	 * @param \Prismic\Document $document
	 * @return \Galahad\Prismoquent\Model
	 */
	public function setDocument(Document $document) : self
	{
		foreach ($document->getData() as $key => $value) {
			$this->setAttribute($key, $value);
		}
		
		$this->document = $document;
		
		return $this;
	}
	
	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return \Galahad\Prismoquent\Builder
	 */
	public function newQuery() : Builder
	{
		return (new Builder(static::$api))->whereType($this->api_id);
	}
	
	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray() : array
	{
		// FIXME
		return $this->attributesToArray();
	}
	
	/**
	 * Convert the model instance to JSON.
	 *
	 * @param  int $options
	 * @return string
	 *
	 * @throws \Illuminate\Database\Eloquent\JsonEncodingException
	 */
	public function toJson($options = 0) : string
	{
		$json = json_encode($this->jsonSerialize(), $options);
		
		if (JSON_ERROR_NONE !== json_last_error()) {
			throw JsonEncodingException::forModel($this, json_last_error_msg());
		}
		
		return $json;
	}
	
	/**
	 * Convert the object into something JSON serializable.
	 *
	 * @return array
	 */
	public function jsonSerialize() : array
	{
		return $this->toArray();
	}
	
	/**
	 * Reload a fresh model instance from the database.
	 *
	 * @param  array|string $with
	 * @return static|null
	 */
	public function fresh() : self
	{
		return new static(
			static::$api->getByUID($this->api_id, $this->document->getUid())
		);
	}
	
	/**
	 * Reload the current model instance with fresh attributes from the database.
	 *
	 * @return \Galahad\Prismoquent\Model
	 */
	public function refresh() : self
	{
		/** @noinspection PhpParamsInspection */
		$this->setDocument(
			static::$api->getByUID($this->api_id, $this->document->getUid())
		);
		
		return $this;
	}
	
	/**
	 * Determine if two models have the same ID and belong to the same table.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model|null $model
	 * @return bool
	 */
	public function is(self $model = null) : bool
	{
		return null !== $model
			&& $this->document->getId() === $model->document->getId();
	}
	
	/**
	 * Determine if two models are not the same.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model|null $model
	 * @return bool
	 */
	public function isNot($model) : bool
	{
		return !$this->is($model);
	}
	
	/**
	 * Get the table associated with the model.
	 *
	 * @return string
	 */
	public function getApiId() : string
	{
		if (null === $this->api_id) {
			return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
		}
		
		return $this->api_id;
	}
	
	/**
	 * Get the document's unique ID
	 *
	 * @return string
	 */
	public function getKey() : ?string
	{
		return $this->document ? $this->document->getId() : null;
	}
	
	/**
	 * Get the document's slug
	 *
	 * @return string
	 */
	public function getRouteKey() : ?string
	{
		return $this->document ? $this->document->getUid() : null;
	}
	
	/**
	 * Retrieve the model for a bound value.
	 *
	 * @param  mixed $value
	 * @return \Galahad\Prismoquent\Model
	 */
	public function resolveRouteBinding($value) : ?self
	{
		/** @var Document $document */
		if ($document = static::$api->getByUID($this->api_id, $value)) {
			return new static($document);
		}
		
		return null;
	}
	
	/**
	 * Get the number of models to return per page.
	 *
	 * @return int
	 */
	public function getPerPage() : int
	{
		return $this->perPage;
	}
	
	/**
	 * Set the number of models to return per page.
	 *
	 * @param  int $perPage
	 * @return \Galahad\Prismoquent\Model
	 */
	public function setPerPage(int $perPage) : self
	{
		if ($perPage > 100) {
			throw new \InvalidArgumentException('You can only query up to 100 Prismic documents at a time.');
		}
		
		$this->perPage = $perPage;
		
		return $this;
	}
	
	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}
	
	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Determine if the given attribute exists.
	 *
	 * @param  mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) : bool
	{
		return null !== $this->getAttribute($offset);
	}
	
	/**
	 * Get the value for a given offset.
	 *
	 * @param  mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->getAttribute($offset);
	}
	
	/**
	 * Set the value for a given offset.
	 *
	 * @param  mixed $offset
	 * @param  mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) : void
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Unset the value for a given offset.
	 *
	 * @param  mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) : void
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Determine if an attribute or relation exists on the model.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function __isset($key)
	{
		return $this->offsetExists($key);
	}
	
	/**
	 * Unset an attribute on the model.
	 *
	 * @param  string $key
	 * @return void
	 */
	public function __unset($key) : void
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Handle dynamic method calls into the model.
	 *
	 * @param  string $method
	 * @param  array $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->newQuery()->$method(...$parameters);
	}
	
	/**
	 * Convert the model to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}
}
