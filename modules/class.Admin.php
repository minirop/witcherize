<?php
class Admin extends Module
{
	public function run($data)
	{
		parent::run($data);
		
		$this->tpl->set('IN_ADMIN', true);
		$this->tpl->set('MODULE', 'admin/index.html');
	}
}
?>