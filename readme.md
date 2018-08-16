# An eloquent way to access Prismic.io content

<p>
	<a href="https://circleci.com/gh/glhd/laravel-prismoquent" target="_blank">
		<img src="https://circleci.com/gh/glhd/laravel-prismoquent.svg?style=svg" alt="CircleCI Build Status" />
	</a>
	<a href="https://coveralls.io/github/glhd/laravel-prismoquent?branch=master" target="_blank">
		<img src="https://coveralls.io/repos/github/glhd/laravel-prismoquent/badge.svg?branch=master" alt="Code Coverage Status" />
	</a>
	<a href="https://packagist.org/packages/glhd/laravel-prismoquent" target="_blank">
        <img src="https://poser.pugx.org/glhd/laravel-prismoquent/v/stable" alt="Stable version on Packagist" />
        <img src="https://poser.pugx.org/glhd/laravel-prismoquent/v/unstable" alt="Dev version on Packagist" />
    </a>
	<a href="license.txt" target="_blank">
        <img src="https://poser.pugx.org/glhd/laravel-prismoquent/license" alt="License" />
    </a>
</p>

This package provides a mostly Eloquent-compatible Model that you can use to access
content from [Prismic.io](https://prismic.io) as though it were a standard Eloquent model. 

```php
class Page extends \Galahad\Prismoquent\Model
{
	// Automatically inferred from class name ("page") if left unset
	protected $type;
	
	// Cast RichText to text or HTML (all other cast types also supported)
	protected $casts = [
		'title' => 'text',
		'meta_description' => 'text',
		'body' => 'html',
	];
	
	// Resolve links as though they were relationships
	public function authorLinkResolver() : ?Person
	{
		return $this->hasOne('author', Person::class);
	}
	
	// Also supports repeating groups of links
	public function similarPagesLinkResolver()
	{
		return $this->hasMany('similar_pages.page', static::class);
	}
}

// Familiar API
$page = Page::where('document.id', 'W2N5Dx8AAD1TPaYt')->first();
$page = Page::find('W2N5Dx8AAD1TPaYt');
$page = Page::findOrFail('W2N5Dx8AAD1TPaYt');

echo "<h1>{$page->title}</h1>";
echo $page->body;

echo "<p>Written by {$page->author->name}</p>"
echo "<h2>Similar Pages</h2>";

foreach ($page->similar_pages as $similar_page) {
	echo "<h3>{$similar_page->title}</h3>";
	echo "<p>{$similar_page->meta_description}</p>";
}

// With full support for all Prismic predicates
Page::where('my.page.body', 'fulltext', 'laravel')->get();
```

## Warning: Active Development

This project is still in active development and may have many bugs. Use at your own risk!

## Installation

You can install the package via composer:
``` bash
composer require glhd/laravel-prismoquent
```

## Usage

See above for a basic example. More details coming soon.

### Link Resolution

You can register link resolvers as either a callable or a route name:

```php
// In your AppServiceProvider
Prismic::registerResolver('page', 'pages.show');
Prismic::registerResolver('person', function(DocumentLink $link) {
	return url('/people/'.$link->getUid());
});

// In your web.php route file
Route::get('/pages/{page}', 'PageController@show')->name('pages.show');
```

If you do not set up a resolver, Prismoquent will try a resource route
for your document. So `Page` will try `route('pages.show', $uid)` or
`NewsItem` will try `route('news_items.show', $uid)`.

Once your resolvers are defined, you can resolve links in any Prismic
Fragment using:

```php
$html = Prismic::asHtml($fragment);
```

## License

The MIT License (MIT). Please see [License File](license.txt) for more information.
