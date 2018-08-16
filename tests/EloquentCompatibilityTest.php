<?php

namespace Galahad\Prismoquent\Tests;

use Galahad\Prismoquent\Exceptions\DocumentNotFoundException;
use Galahad\Prismoquent\Results;
use Illuminate\Support\Carbon;

class EloquentCompatibilityTest extends TestCase
{
	public function test_it_can_load_a_specific_piece_of_content() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		$this->assertInstanceOf(Page::class, $page);
	}
	
	public function test_is_can_load_all_content() : void
	{
		$pages = Page::all();
		
		$this->assertGreaterThan(0, $pages->count());
		
		foreach ($pages as $page) {
			$this->assertInstanceOf(Page::class, $page);
		}
	}
	
	public function test_content_can_be_queried() : void
	{
		$pages = Page::where('my.page.title', 'fulltext', 'second demo')
			->orderBy('first_publication_date')
			->take(1)
			->get();
		
		$this->assertEquals(1, $pages->count());
		$this->assertInstanceOf(Page::class, $pages->first());
		$this->assertContains('second demo', strtolower($pages->first()->title));
	}
	
	public function test_content_can_be_loaded_by_uid() : void
	{
		$page = Page::findByUID('demo-page');
		
		$this->assertInstanceOf(Page::class, $page);
		$this->assertEquals('W3RRKh0AADaEY847', $page->id);
	}
	
	public function test_find_can_be_passed_multiple_ids() : void
	{
		$pages = Page::find(['W3UE7x0AANRvZubU', 'W3RRKh0AADaEY847']);
		
		$this->assertInstanceOf(Results::class, $pages);
		
		$ids = $pages->pluck('id');
		
		$this->assertTrue($ids->contains('W3UE7x0AANRvZubU'));
		$this->assertTrue($ids->contains('W3RRKh0AADaEY847'));
	}
	
	public function test_it_throws_an_exception_on_findOrFail() : void
	{
		$this->expectException(DocumentNotFoundException::class);
		
		Page::findOrFail('this is definitely not a Prismic ID');
	}
	
	public function test_it_throws_an_exception_on_firstOrFail() : void
	{
		$this->expectException(DocumentNotFoundException::class);
		
		Page::where('document.id', '=', 'no first result')->firstOrFail();
	}
	
	public function test_it_resolves_documents_by_slug_for_routing() : void
	{
		$page = (new Page())->resolveRouteBinding('demo-page');
		
		$this->assertInstanceOf(Page::class, $page);
		$this->assertEquals('W3RRKh0AADaEY847', $page->id);
	}
	
	public function test_it_casts_prismic_timestamps_to_carbon() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		$this->assertInstanceOf(Carbon::class, $page->first_publication_date);
		$this->assertInstanceOf(Carbon::class, $page->last_publication_date);
	}
}
