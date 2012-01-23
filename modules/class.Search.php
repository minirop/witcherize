<?php
class Search extends Module
{
	public function run($data)
	{
		parent::run($data);
		
		if(!empty($_POST['keyword']))
		{
			$search = str_replace(' ', '+', $_POST['keyword']);
			header('location:'.$this->config['root_path'].'/post/search/'.$search);
		}
		else
			header('location:'.$this->config['root_path'].'/post');
		exit;
	}
}
?>