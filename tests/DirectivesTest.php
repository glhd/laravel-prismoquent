<?php

namespace Galahad\Prismoquent\Tests;

use Illuminate\Support\Facades\Blade;

class DirectivesTest extends TestCase
{
	public function test_it_compiles_slice_directive_to_valid_php() : void
	{
		$expect = '<?php \Galahad\Prismoquent\Facades\Prismic::sliceComponent($__env, $sliceObject); ?>';
		$compiled = Blade::compileString('@slice($sliceObject)');
		
		$this->assertEquals($expect, $compiled);
	}
	
	public function test_it_compiles_slices_directive_to_valid_php() : void
	{
		$expect = '<?php foreach($sliceObject->getSlices() as $__prismoquent_slice): \Galahad\Prismoquent\Facades\Prismic::sliceComponent($__env, $__prismoquent_slice); endforeach; ?>';
		$compiled = Blade::compileString('@slices($sliceObject)');
		
		$this->assertEquals($expect, $compiled);
	}
}
