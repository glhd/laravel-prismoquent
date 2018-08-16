<?php

namespace Galahad\Prismoquent\Tests;

class CastingTest extends TestCase
{
	public function test_structured_text_can_be_accessed_as_html() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		$html = $page->body->asHtml();
		
		$this->assertTrue(str_contains($html, '<strong>'));
	}
	
	public function test_structured_text_can_be_accessed_as_text() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		$html = $page->body->asText();
		
		$this->assertFalse(str_contains($html, '<strong>'));
	}
}
