<?php

namespace Galahad\Prismoquent\Tests;

use Illuminate\Support\Facades\Blade;

class DirectivesTest extends TestCase
{
	public function test_it_compiles_slice_directive_to_valid_php() : void
	{
		$expect = '<?php $__env->startComponent($sliceObject->getSliceType(), [\'slice\' => $sliceObject]); echo $__env->renderComponent(); ?>';
		$compiled = Blade::compileString('@slice($sliceObject)');
		
		$this->assertEquals($expect, $compiled);
	}
	
	public function test_it_compiles_slices_directive_to_valid_php() : void
	{
		$expect = '<?php foreach($sliceObject->getSlices() as $__prismoquent_slice): $__env->startComponent($__prismoquent_slice->getSliceType(), [\'slice\' => $__prismoquent_slice]); echo $__env->renderComponent(); endforeach; ?>';
		$compiled = Blade::compileString('@slices($sliceObject)');
		
		$this->assertEquals($expect, $compiled);
	}
}
