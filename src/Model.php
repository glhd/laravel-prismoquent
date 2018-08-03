<?php

namespace Galahad\Prismoquent;

use ArrayAccess;
use DateTime;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Str;
use JsonSerializable;
use Prismic\Api;
use RuntimeException;

abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, UrlRoutable
{
	use HasAttributes, HasEvents, HidesAttributes;
	
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
	 * @var \stdClass
	 */
	public $document;
	
	/**
	 * The API ID of the content type this represents
	 *
	 * @var string
	 */
	protected $type;
	
	/**
	 * Create a new Prismoquent model instance.
	 *
	 * @param \stdClass $document
	 */
	public function __construct($document = null)
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
	 * Get the observable event names.
	 *
	 * @return array
	 */
	public function getObservableEvents()
	{
		return array_merge(['retrieved'], $this->observables);
	}
	
	/**
	 * @param \stdClass $document
	 * @return \Galahad\Prismoquent\Model
	 */
	public function setDocument($document) : self
	{
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
		return (new Builder(static::$api, $this))
			->whereType($this->getType());
	}
	
	public function newInstance($document)
	{
		return new static($document);
	}
	
	/**
	 * Create a new model instance from a document retrieved via a Builder
	 *
	 * @param \stdClass $document
	 * @return static
	 */
	public function newFromBuilder($document)
	{
		$model = $this->newInstance($document);
		
		$model->fireModelEvent('retrieved', false);
		
		return $model;
	}
	
	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray() : array
	{
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
		/** @noinspection PhpParamsInspection */
		return $this->newFromBuilder(
			static::$api->getByUID($this->getType(), $this->document->uid)
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
			static::$api->getByUID($this->getType(), $this->document->uid)
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
			&& $this->document->id === $model->document->id;
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
	public function getType() : string
	{
		if (null === $this->type) {
			return str_replace('\\', '', Str::snake(class_basename($this)));
		}
		
		return $this->type;
	}
	
	/**
	 * Get the document's unique ID
	 *
	 * @return string
	 */
	public function getKey() : ?string
	{
		return $this->document->id ?? null;
	}
	
	/**
	 * Get the document's slug
	 *
	 * @return string
	 */
	public function getRouteKey() : ?string
	{
		return $this->document->id ?? null;
	}
	
	/**
	 * @return string
	 */
	public function getRouteKeyName() : string
	{
		return 'uid';
	}
	
	/**
	 * Retrieve the model for a bound value.
	 *
	 * @param  mixed $value
	 * @return \Galahad\Prismoquent\Model
	 */
	public function resolveRouteBinding($value) : ?self
	{
		/** @var \stdClass $document */
		if ($document = static::$api->getByUID($this->getType(), $value)) {
			return $this->newFromBuilder($document);
		}
		
		return null;
	}
	
	/**
	 * Get attribute value
	 *
	 * @param $key
	 * @return mixed|null
	 */
	public function getAttribute($key)
	{
		if (!$key) {
			return null;
		}
		
		return $this->getAttributeValue($key);
	}
	
	/**
	 * Determine if a get mutator exists for an attribute.
	 *
	 * @param  string $key
	 * @return bool
	 */
	public function hasGetMutator($key)
	{
		$mutator_key = str_replace('.', '_', $key);
		return method_exists($this, 'get'.Str::studly($mutator_key).'Attribute');
	}
	
	/**
	 * Set a given attribute on the model.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed
	 */
	public function setAttribute($key, $value)
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Set a given JSON attribute on the model.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return $this
	 */
	public function fillJsonAttribute($key, $value)
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Get all of the current attributes on the model.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return (array) $this->document->data;
	}
	
	/**
	 * Set the array of model attributes. No checking is done.
	 *
	 * @param  array $attributes
	 * @param  bool $sync
	 * @return $this
	 */
	public function setRawAttributes(array $attributes, $sync = false)
	{
		throw new RuntimeException('Prismoquent models are read-only.');
	}
	
	/**
	 * Get the attributes that should be converted to dates.
	 *
	 * @return array
	 */
	public function getDates()
	{
		return array_unique(array_merge($this->dates, ['first_publication_date', 'last_publication_date']));
	}
	
	/**
	 * Get the format for database stored dates.
	 *
	 * @return string
	 */
	public function getDateFormat()
	{
		return DateTime::ISO8601;
	}
	
	/**
	 * Tell HasAttributes to not try to handle auto-increment on this
	 *
	 * @return bool
	 */
	public function getIncrementing()
	{
		return false;
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
	
	/**
	 * Get the value of an attribute using its mutator.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed
	 */
	protected function mutateAttribute($key, $value)
	{
		$mutator_key = str_replace('.', '_', $key);
		return $this->{'get'.Str::studly($mutator_key).'Attribute'}($value);
	}
	
	/**
	 * Get an attribute array of all arrayable attributes.
	 *
	 * @return array
	 */
	protected function getArrayableAttributes()
	{
		return $this->getArrayableItems($this->getAttributes());
	}
	
	/**
	 * Get an attribute from the $attributes array.
	 *
	 * @param  string $key
	 * @return mixed
	 */
	protected function getAttributeFromArray($key)
	{
		return 'data' === $key
			? new Data($this)
			: object_get($this->document, $key);
	}
	
	/**
	 * Get an array attribute or return an empty array if it is not set.
	 *
	 * @param  string $key
	 * @return array
	 */
	protected function getArrayAttributeByKey($key)
	{
		return $this->getAttributeFromArray($key);
	}
}
