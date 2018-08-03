# An eloquent way to access Prismic.io content

This package provides a mostly Eloquent-compatible Model that you can use to access
content from [Prismic.io](https://prismic.io) as though it were a standard Eloquent model. 

```php
class Page extends \Galahad\Prismoquent\Model
{
	// Automatically inferred as "page"
	protected $type;
	
	// Eager load links using Prismic's fetchLinks option
	protected $with = [];
	
	// Cast RichText to text or HTML (all other cast types also supported)
	protected $casts = [
		'data.meta_description' => 'text',
		'data.body' => 'html',
	];
	
	// Resolve links as though they were relationships
	public function authorLinkResolver() : ?Person
	{
		return $this->oneLink('author');
	}
	
	// Also supports repeating groups of links
	public function similarPagesLinkResolver() : Collection
	{
		return $this->manyLinks('similar_pages.page');
	}
}

// Familiar API
$page = Page::where('document.id', 'W2N5Dx8AAD1TPaYt')->first();

foreach ($page->similar_pages as $similar_page) {
	// $similar_page instanceof Page
}

$author_name = $page->author->name;

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
