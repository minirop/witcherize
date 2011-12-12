<?php
class Profile extends Module
{
	public function run($data)
	{
		$error = '';
		if(count($data))
		{
			$req = $this->db->prepare('SELECT `users`.`id`, `username`, `email`, `name` FROM `users` JOIN `groups` ON `group_id` = `groups`.`id` WHERE `username` = ? LIMIT 1');
			$req->execute(array($data[0]));
			$userdata = $req->fetch(PDO::FETCH_ASSOC);
			$req->closeCursor();
			if(!$userdata)
			{
				$error = 'This username does not exists.';
			}
		}
		else
		{
			$error = 'No username given.';
		}
		
		if($error == '')
		{
			$req = $this->db->prepare('SELECT COUNT(*) FROM `images` WHERE `user_id` = ? LIMIT 1');
			$req->execute(array($userdata['id']));
			$userdata['count_images'] = $req->fetchColumn(0);
			$req->closeCursor();
			
			$req = $this->db->prepare('SELECT * FROM `images` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 5');
			$req->execute(array($userdata['id']));
			$userdata['last_images'] = $req->fetchAll(PDO::FETCH_ASSOC);
			$req->closeCursor();
			
			$this->tpl->set('USERDATA', $userdata);
			$this->tpl->set('MODULE', 'profile.html');
		}
		else
		{
			$this->tpl->set('FLASH_MESSAGE', $error);
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post');
			$this->tpl->set('REDIRECT_TIME', 10);
			$this->tpl->set('MODULE', 'flash.html');
		}
	}
}
?>