<?php
class Help extends Module
{
	public function run($data)
	{
		$this->tpl->set('IN_HELP', true);
		$this->tpl->set('MODULE', 'help.html');
	}
}
?>