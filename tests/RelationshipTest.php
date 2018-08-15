<?php

namespace Galahad\Prismoquent\Tests;

class RelationshipTest extends TestCase
{
	public function test_links_are_automatically_loaded() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		$this->assertInstanceOf(Person::class, $page->author);
	}
	
	public function test_resolves_has_one_relationships() : void
	{
		$this->markTestIncomplete();
	}
	
	public function test_resolves_has_many_relationships() : void
	{
		$this->markTestIncomplete();
	}
}
