<?php
class Index extends Module
{
	public function run($data)
	{
		parent::run($data);
		
		$req = $this->db->query('SELECT COUNT(*) FROM `images`');
		$total_images = $req->fetchColumn(0);
		$req->closeCursor();
		
		$cnt = str_split($total_images);
		$this->tpl->set('CNT', $cnt);
		$this->tpl->parse('index.html');
	}
}
?>