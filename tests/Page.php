<?php

namespace Galahad\Prismoquent\Tests;

use Galahad\Prismoquent\Model;

class Page extends Model
{
	protected $casts = [
		'title' => 'text',
	];
}
