<?php
namespace ObjectMiner\Structures;

class DataStructure
{
	public $name;
	public $group;
	public $elements;
	public $parent;
	public $pos;

	public function __construct()
	{
		$this->name     = '';
		$this->group    = new ElementStructure();
		$this->elements = [];
		$this->parent   = null;
		$this->pos      = 0;
	}
}
