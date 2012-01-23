<?php
class Mail extends Module
{
	public function run($data)
	{
		parent::run($data);
		
		if(!count($data))
		{
			header('location:'.$this->config['root_path'].'/post');
			exit;
		}
		
		switch($data[0])
		{
			case 'regenerate':
				$this->regenerate_password();
				break;
		}
	}
	
	private function regenerate_password()
	{
		if(empty($_POST['email']))
		{
			header('location:'.$this->config['root_path'].'/post');
			exit;
		}
		
		if(!preg_match('/[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]/i', $_POST['email']))
		{
			$this->tpl->set('FLASH_MESSAGE', 'This email is not valid.');
			$this->tpl->set('REDIRECT_TIME', 5);
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/account/lost+password');
		}
		else
		{
			$req = $this->db->prepare('SELECT COUNT(*) FROM `users` WHERE `email` = ? LIMIT 1');
			$req->execute(array($_POST['email']));
			$email_exists = $req->fetchColumn(0);
			$req->closeCursor();
			
			$this->tpl->set('REDIRECT_TIME', 5);
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post');
			if($email_exists)
			{
				$uid = md5(uniqid());
				$message = sprintf($this->lang['mail-regenerate-password'], 'http://'.$_SERVER['SERVER_NAME'].$this->config['root_path'].'/mail/confirm/'.$uid);
				$this->send_mail($_POST['email'], $message);
			}
			else
				$this->tpl->set('FLASH_MESSAGE', 'This Email Are Not Belong To Us!');
		}
		
		$this->tpl->set('MODULE', 'flash.html');
	}
	
	private function send_mail($to, $message)
	{
		$subject = 'Confirm your new password';
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
		$headers .= 'From: ' . $this->config['sending_email'] . "\r\n";
		$headers .= 'Reply-To: ' . $this->config['sending_email'] . "\r\n";

		if(@mail($to, $subject, $message, $headers))
		{
			$this->tpl->set('FLASH_MESSAGE', 'You got mail!');
		}
		else
		{
			$this->tpl->set('FLASH_MESSAGE', 'Error while trying to send the e-mail!');
			$this->tpl->set('REDIRECT_TIME', 15);
		}
	}
}
?>