<?php

namespace Galahad\Prismoquent\Tests;

use Illuminate\Support\Collection;

class RelationshipTest extends TestCase
{
	public function test_links_are_automatically_inferred() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		$this->assertInstanceOf(Person::class, $page->author);
	}
	
	public function test_resolves_has_one_relationships() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		$this->assertInstanceOf(Person::class, $page->resolved_author);
	}
	
	public function test_resolves_has_many_relationships() : void
	{
		$directory = Directory::query()->first();
		$people = $directory->people;
		
		$this->assertInstanceOf(Collection::class, $people);
		$this->assertInstanceOf(Person::class, $people->first());
	}
}
