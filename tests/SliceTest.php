<?php

namespace Galahad\Prismoquent\Tests;

use Prismic\Fragment\CompositeSlice;
use Prismic\Fragment\GroupDoc;
use Prismic\Fragment\SliceZone;

class SliceTest extends TestCase
{
	public function test_slices() : void
	{
		$page = Page::find('W3RRKh0AADaEY847');
		
		/** @var SliceZone $slices */
		$slices = $page->slices;
		
		$this->assertInstanceOf(SliceZone::class, $slices);
		
		/** @var CompositeSlice $slice */
		$slice = $slices->getSlices()[0];
		
		$this->assertInstanceOf(CompositeSlice::class, $slice);
		$this->assertEquals('quote', $slice->getSliceType());
		
		/** @var GroupDoc $group */
		$group = $slice->getPrimary();
		
		$group->asHtml();
	}
}
