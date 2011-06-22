<?php
class Account extends Module
{
	public function run($data)
	{
		$this->tpl->set('IN_ACCOUNT', true);
		$this->tpl->set('USER', $this->user);
		
		if(count($data))
		{
			switch($data[0])
			{
				case 'check':
					$this->check_connection();
					break;
				case 'logout':
					$this->logout();
					break;
				case 'lost+password':
					$this->lost_password();
					break;
				default:
					{
						header('location:'.$this->config['root_path'].'/account');
						exit;
					}
			}
		}
		else
		{
			if($this->user)
				$this->tpl->set('MODULE', 'account.html');
			else
				$this->tpl->set('MODULE', 'login.html');
		}
	}
	
	private function logout()
	{
		session_destroy();
		header('location:'.$this->config['root_path'].'/account');
		exit;
	}
	
	private function check_connection()
	{
		if(empty($_POST['login']) || empty($_POST['password']))
		{
			header('location:'.$this->config['root_path'].'/account');
			exit;
		}
		
		$req = $this->db->prepare('SELECT COUNT(*) FROM `users` WHERE `username` = ? AND `password` = ? LIMIT 1');
		$req->execute(array(
			$_POST['login'],
			sha1($_POST['password'])
		));
		$user_exists = $req->fetchColumn(0);
		$req->closeCursor();
		
		if($user_exists)
		{
			$_SESSION['login'] = $_POST['login'];
			$_SESSION['password'] = sha1($_POST['password']);
			$this->tpl->set('FLASH_MESSAGE', 'You are now connected.');
			$this->tpl->set('REDIRECT_TIME', 5);
		}
		else
		{
			$this->tpl->set('FLASH_MESSAGE', 'The couple user/password does not exists.');
			$this->tpl->set('REDIRECT_TIME', 99);
		}
		$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/account');
		$this->tpl->set('MODULE', 'flash.html');
	}
	
	private function lost_password()
	{
		$this->tpl->set('MODULE', 'lost_password.html');
	}
	
	private function regenerate_password()
	{
		$this->tpl->set('MODULE', 'flash.html');
	}
}
?>