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
    <a href="https://github.styleci.io/repos/143375310">
        <img src="https://github.styleci.io/repos/143375310/shield?branch=master" alt="Code Style Status">
	</a>
	<a href="license.txt" target="_blank">
        <img src="https://poser.pugx.org/glhd/laravel-prismoquent/license" alt="License" />
    </a>
</p>

This package provides a mostly Eloquent-compatible Model that you can use to access
content from [Prismic.io](https://prismic.io) as though it were a standard Eloquent model.

#### App/Page.php 

```php
class Page extends \Galahad\Prismoquent\Model
{
	// Automatically inferred from class name ("page") if left unset
	protected $type;
	
	// Cast RichText to text or HTML (all other cast types also supported)
	protected $casts = [
		'title' => 'text',
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
```

#### App/Http/Controllers/PageController.php

```php
class PageController extend Controller
{
	public function show(Page $page)
	{
		return view('page.show', compact('page'));
	}
}
```

#### resources/views/page/show.blade.php

```blade

<h1>{{ $page->title }}</h1>

<div class="page-body">
	{{ $page->body }}
</div>

```

```php
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

### Configuration

Looks for config in your `services.php` file:

```php
return [
	'prismic' => [
		'endpoint' => env('PRISMIC_ENDPOINT'), // Required
		'api_token' => env('PRISMIC_API_TOKEN'), // Optional, depending on your Prismic permissions
		'webhook_secret' => env('PRISMIC_WEBHOOK_SECRET'), // Optional, if you're using build-in controller
		'register_controller' => false, // Set to false to disable Webhook controller
    ]
];
```

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

### Blade Directives

```blade

{{-- Will render slice object using views/slices/slice-type.blade.php --}}
@slice($object_implementing_slice_tnterface)

{{-- Will render all slices in slice zone using @slice directive --}}
@slice($slice_zone_object)

{{-- Converts frament to HTML using link resolver --}}
@asHtml($fragment)

{{-- Converts frament to plain text --}}
@asText($fragment)

{{-- Converts a DocumentLink fragment to the resolved URL --}}
@resolveLink($documentLink)

```

## License

The MIT License (MIT). Please see [License File](license.txt) for more information.
