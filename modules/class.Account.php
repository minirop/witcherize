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
				case 'register':
					$this->register();
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
	
	private function register()
	{
		if(!empty($_POST))
		{
			$mandatory_fields = array('name', 'password', 'email');
			$all = true;
			foreach($mandatory_fields AS $v)
			{
				if(empty($_POST[$v]))
					$all = false;
			}
			
			if($all)
			{
				$req = $this->db->prepare('SELECT COUNT(*) FROM `users` WHERE LOWER(`username`) = LOWER(?) OR LOWER(`email`) = LOWER(?) LIMIT 1');
				$req->execute(array($_POST['name'], $_POST['email']));
				$totalMembre = $req->fetchColumn(0);
				$req->closeCursor();
				
				$error = '';
				
				if($totalMembre)
				{
					$error = 'Username or e-mail alreay in use.';
				}
				
				// 2 to 6 for extension 'cause .travel (even if not (really) use for now)
				if(!preg_match('/.+@[\.a-zA-Z0-9-]+\.[a-zA-Z]{2,6}/', $_POST['email']))
				{
					$error = 'Invalid e-mail already in use.';
				}
				
				if($error != '')
				{
					$this->tpl->set('FLASH_MESSAGE', $error);
					$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/account/register');
					$this->tpl->set('REDIRECT_TIME', 10);
					$this->tpl->set('MODULE', 'flash.html');
					return;
				}
				
				$req = $this->db->prepare('INSERT INTO `users` VALUES (NULL, ?, SHA1(?), ?, 1)');
				$req->execute(array(
					$_POST['name'],
					$_POST['password'],
					$_POST['email'],
				));
				$req->closeCursor();
				
				$this->tpl->set('FLASH_MESSAGE', 'Registration successful.');
				$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/account');
			}
			else
			{
				$this->tpl->set('FLASH_MESSAGE', 'All fields are mandatory.');
				$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/account/register');
			}
			$this->tpl->set('REDIRECT_TIME', 5);
			$this->tpl->set('MODULE', 'flash.html');
		}
		else
		{
			$this->tpl->set('MODULE', 'register.html');
		}
	}
}
?>