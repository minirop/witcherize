<?php
class Module
{
	public $tpl = null;
	public $db = null;
	
	public function run($data)
	{
		if($this->user)
		{
			$this->tpl->set('IS_CONNECTED', 1);
			if(!empty($this->user['isModo']))
				$this->tpl->set('IS_MODO', 1);
			if(!empty($this->user['isAdmin']))
				$this->tpl->set('IS_ADMIN', 1);
		}
	}
}
?>