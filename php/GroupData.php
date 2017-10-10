<?php
require_once './php/GroupElement.php';

class GroupData
{
	public $name;
	public $group;
	public $elements;
	public $parent;
	public $pos;

	public function __construct()
	{
		$this->name     = '';
		$this->group    = new GroupElement();
		$this->elements = [];
		$this->parent   = null;
		$this->pos      = 0;
	}
}
