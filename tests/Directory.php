<?php

namespace Galahad\Prismoquent\Tests;

use Galahad\Prismoquent\Model;

class Directory extends Model
{
	public function peopleLinkResolver()
	{
		return $this->hasMany('people.person', Person::class);
	}
}
