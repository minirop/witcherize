<?php
class Index extends Module
{
	public function __construct()
	{
	}
	
	public function run($data)
	{
		$cnt = array(4, 4, 4);
		$this->tpl->set('CNT', $cnt);
		$this->tpl->parse('index.html');
	}
}
?>