<?php

namespace Galahad\Prismoquent\Tests;

class EloquentCompatibilityTest extends TestCase
{
	public function test_it_can_load_a_specific_piece_of_content() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		$this->assertInstanceOf(Page::class, $page);
	}
}
