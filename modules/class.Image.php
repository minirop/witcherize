<?php
class Image extends Module
{
	public function run($data)
	{
		if(!count($data) || !$this->user)
		{
			header('location:'.$this->config['root_path'].'/account');
			exit;
		}
		
		switch($data[0])
		{
			case 'uploaded':
				$this->upload_file();
				break;
			case 'upload':
				$this->tpl->set('MODULE', 'upload.html');
				break;
		}
	}
	
	private function upload_file()
	{
		if(empty($_FILES['file']))
		{
			header('location:'.$this->config['root_path'].'/image/upload');
			exit;
		}
		
		if($_FILES['file']['error'])
		{
			$this->tpl->set('REDIRECT_TIME', 10);
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/image/upload');
			$this->tpl->set('FLASH_MESSAGE', 'Error while uploading the file. Try again.');
			$this->tpl->set('MODULE', 'flash.html');
			return;
		}
		
		$image = imagecreatefromjpeg($_FILES['file']['tmp_name']);
		$image_th = imagecreatetruecolor(150, 150);
		if(move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/zz/'.$_FILES['file']['name']))
		{
			imagecopyresampled($image_th, $image, 0, 0, 0, 0, 150, 150, imagesx($image), imagesy($image));
			imagejpeg($image_th, 'uploads/zz/th/'.$_FILES['file']['name']);
		}
		
		$req = $this->db->prepare('INSERT INTO `images`(`image`, `dossier`, `user_id`, `created`, `width`, `height`) VALUES(?, ?, ?, NOW(), ?, ?)');
		echo $req->execute(array(
			$_FILES['file']['name'],
			'zz',
			1,
			0,
			0
		));
		
		$this->tpl->set('REDIRECT_TIME', 10);
		$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post/'.$this->db->lastInsertId());
		$this->tpl->set('FLASH_MESSAGE', 'Image uploaded.');
		$this->tpl->set('MODULE', 'flash.html');
	}
}
?>