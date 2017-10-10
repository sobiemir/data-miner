<?php

class GroupElement
{
	public $content;
	public $start;
	public $startLine;
	public $end;
	public $endLine;
	public $subGroups;

	public function __construct()
	{
		$this->content   = "";
		$this->start     = 0;
		$this->end       = 0;
		$this->subGroups = [];
		$this->startLine = 0;
		$this->endLine   = 0;
	}
}