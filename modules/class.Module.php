<?php
abstract class Module
{
	public $tpl = null;
	public $db = null;
	
	abstract public function run($data);
}
?>