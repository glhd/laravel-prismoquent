<?php

namespace Galahad\Prismoquent;

use ArrayAccess;
use DateTime;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonSerializable;
use Prismic\Api;
use Prismic\Document;
use Prismic\Fragment\FragmentInterface;
use Prismic\Fragment\Group;
use Prismic\Fragment\GroupDoc;
use Prismic\Fragment\Link\DocumentLink;
use RuntimeException;

/**
 * @mixin \Galahad\Prismoquent\Builder
 */
abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable, UrlRoutable
{
	use HasRelationships, HasEvents, HidesAttributes, HasAttributes {
		castAttribute as eloquentCastAttribute;
	}
	
	protected const DOCUMENT_ATTRIBUTES = [
		'slug' => 'getSlug',
		'id' => 'getId',
		'uid' => 'getUid',
		'type' => 'getType',
		'href' => 'getHref',
		'tags' => 'getTags',
		'slugs' => 'getSlugs',
		'lang' => 'getLang',
		'alternate_languages' => 'getAlternateLanguages',
		'data' => 'getData',
		'first_publication_date' => 'getFirstPublicationDate',
		'last_publication_date' => 'getLastPublicationDate',
	];
	
	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected static $dispatcher;
	
	/**
	 * @var \Galahad\Prismoquent\Prismoquent
	 */
	protected static $api;
	
	/**
	 * The original Prismic document
	 *
	 * @var \Prismic\Document
	 */
	public $document;
	
	/**
	 * The API ID of the content type this represents
	 *
	 * @var string
	 */
	protected $type;
	
	/**
	 * Links to always eager load
	 *
	 * @var array
	 */
	protected $with = [];
	
	/**
	 * Create a new Prismoquent model instance.
	 *
	 * @param \stdClass $document
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
	public static function setApi(Prismoquent $api) : void
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
	 * Find model by ID
	 *
	 * @param $id
	 * @return \Galahad\Prismoquent\Model|\Galahad\Prismoquent\Results|null
	 */
	public static function find($id)
	{
		return static::query()->find($id);
	}
	
	/**
	 * Find model by UID/slug
	 *
	 * @param $uid
	 * @return \Galahad\Prismoquent\Model|null
	 */
	public static function findByUID($uid) : ?self
	{
		$instance = new static();
		return $instance->newQuery()->findByUID($instance->getType(), $uid);
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
	 * Begin querying a model with eager loading links
	 *
	 * @param  array|string $relations
	 * @return \Galahad\Prismoquent\Builder
	 */
	public static function with($relations) : Builder
	{
		return static::query()->with(
			is_string($relations) ? func_get_args() : $relations
		);
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
	public function setDocument(Document $document) : self
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
			->with($this->with)
			->whereType($this->getType());
	}
	
	/**
	 * Create a new instance of the given model
	 *
	 * @param object $document
	 * @return static
	 */
	public function newInstance(Document $document)
	{
		return new static($document);
	}
	
	/**
	 * Create a new model instance from a document retrieved via a Builder
	 *
	 * @param \stdClass|\Prismic\Prismic $document
	 * @return static
	 */
	public function newFromBuilder(Document $document)
	{
		$model = $this->newInstance($document);
		
		$model->fireModelEvent('retrieved', false);
		
		return $model;
	}
	
	/**
	 * Eager load relations on the model.
	 *
	 * @param  array|string $relations
	 * @return $this
	 */
	public function load($relations)
	{
		$query = $this->newQueryWithoutRelationships()->with(
			is_string($relations) ? func_get_args() : $relations
		);
		
		$query->eagerLoadRelations([$this]);
		
		return $this;
	}
	
	/**
	 * Eager load relations on the model if they are not already eager loaded.
	 *
	 * @param  array|string $relations
	 * @return $this
	 */
	public function loadMissing($relations)
	{
		$relations = is_string($relations) ? func_get_args() : $relations;
		
		$this->newCollection([$this])->loadMissing($relations);
		
		return $this;
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
		return $this->newQuery()->find($this->document->getId());
	}
	
	/**
	 * Reload the current model instance with fresh attributes from the database.
	 *
	 * @return \Galahad\Prismoquent\Model
	 */
	public function refresh() : self
	{
		/** @noinspection PhpParamsInspection */
		$this->setDocument($this->fresh()->document);
		
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
	public function getType() : string
	{
		if (null === $this->type) {
			$this->type = str_replace('\\', '', Str::snake(class_basename($this)));
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
		return $this->document->getId() ?? null;
	}
	
	/**
	 * Get the document's slug
	 *
	 * @return string
	 */
	public function getRouteKey() : ?string
	{
		return $this->document->getUid() ?? null;
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
		return $this->newQuery()->findByUID($this->getType(), $value);
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
		
		// Unlike Eloquent, where relationships are loaded via a column with
		// a separate name, Prismic links are likely to share the desired name.
		// We use the 'LinkResolver' suffix, and look for relationships first
		// to address this (otherwise getAttribute would almost always hit first.
		if ($related = $this->getRelationValue(Str::camel($key).'LinkResolver')) {
			return $related;
		}
		
		$value = $this->getAttributeValue($key);
		
		if ($value instanceof DocumentLink) {
			return $this->resolveLink($value);
		}
		
		return $value;
	}
	
	public function getCasts()
	{
		// $type = $this->getType();
		//
		// return collect($this->casts)
		// 	->mapWithKeys(function($value, $key) use ($type) {
		// 		return ["data.{$type}.$key" => $value];
		// 	})
		// 	->toArray();
		
		return $this->casts;
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
		return (array) $this->document->getData();
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
	
	protected function getDocumentAttribute($key)
	{
		if (isset(static::DOCUMENT_ATTRIBUTES[$key])) {
			$method = static::DOCUMENT_ATTRIBUTES[$key];
			return $this->document->$method();
		}
		
		return null;
	}
	
	/**
	 * Cast an attribute to a native PHP type.
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed
	 */
	protected function castAttribute($key, $value)
	{
		if (null === $value) {
			return $value;
		}
		
		if ($value instanceof FragmentInterface) {
			$cast = $this->getCastType($key);
			
			if ('html' === $cast) {
				return $value->asHtml(static::$api->resolver);
			}
			
			if ('text' === $cast) {
				return $value->asText();
			}
			
			// TODO: Dates
		}
		
		return $this->eloquentCastAttribute($key, $value);
	}
	
	/**
	 * Resolve a link as a relationship
	 *
	 * @param  string $method
	 * @return mixed
	 *
	 * @throws \LogicException
	 */
	protected function getRelationshipFromMethod($method)
	{
		$relation = $this->$method();
		
		if ($relation instanceof Collection) {
			$this->validateRelationType($method, $relation->first());
		} else {
			$this->validateRelationType($method, $relation);
		}
		
		$this->setRelation($method, $relation);
		
		return $relation;
	}
	
	/**
	 * Ensure that the relation either returns a Model or a Collection of models
	 *
	 * @param $method
	 * @param $relation
	 */
	protected function validateRelationType($method, $relation) : void
	{
		if (!$relation instanceof self) {
			throw new \LogicException(sprintf(
				'%s::%s must return a Prismoquent model instance.', static::class, $method
			));
		}
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
		if ($value = $this->getDocumentAttribute($key)) {
			return $value;
		}
		
		// Unlike Eloquent, where relationships are loaded via a column with
		// a separate name, Prismic links are likely to share the desired name.
		// We use the 'LinkResolver' suffix, and look for relationships first
		// to address this (otherwise getAttribute would almost always hit first.
		if ($related = $this->getRelationValue("{$key}LinkResolver")) {
			return $related;
		}
		
		$type = $this->getType();
		$value = $this->document->get("{$type}.{$key}");
		
		if ($value instanceof DocumentLink) {
			return $this->resolveLink($value);
		}
		
		return $value;
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
	
	protected function hasOne($path, $class_name = null)
	{
		$type = $this->getType();
		$link = $this->document->get("{$type}.{$path}");
		
		return $link instanceof DocumentLink
			? $this->resolveLink($link, $class_name)
			: null;
	}
	
	protected function hasMany($path, $class_name = null) : Collection
	{
		$segments = explode('.', $path);
		$link_key = array_pop($segments);
		$group_path = implode('.', $segments);
		$type = $this->getType();
		
		$group = $this->document->get("{$type}.{$group_path}");
		
		if ($group instanceof Group) {
			return Collection::make($group->getArray())
				->map(function(GroupDoc $doc) use ($link_key, $class_name) {
					$link = $doc->get($link_key);
					return $link instanceof DocumentLink
						? $this->resolveLink($link, $class_name)
						: null;
				})
				->filter();
		}
		
		return new Collection();
	}
	
	protected function resolveLink(DocumentLink $link, $class_name = null) : ?self
	{
		/** @var self $model_class */
		$model_class = $class_name ?? $this->inferLinkClassName($link->getType());
		
		return $model_class::find($link->getId());
	}
	
	protected function inferLinkClassName($type) : string
	{
		$namespace = substr(static::class, 0, strrpos(static::class, '\\'));
		$class_name = studly_case($type);
		
		return "{$namespace}\\{$class_name}";
	}
}
