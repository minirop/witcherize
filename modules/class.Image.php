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
		
		$dir = substr($_FILES['file']['name'], 0, 2);
		if(!is_dir('uploads/'.$dir))
		{
			mkdir('uploads/'.$dir.'/th', 0777, true);
		}
		
		$image = imagecreatefromjpeg($_FILES['file']['tmp_name']);
		$iw = imagesx($image);
		$ih = imagesy($image);
		// calcul thumb's dimensions
		if($iw > $ih)
		{
			$thw = 150;
			$thh = intval($ih * 150 / $iw);
		}
		else
		{
			$thh = 150;
			$thw = intval($iw * 150 / $ih);
		}
		// END
		
		$image_th = imagecreatetruecolor($thw, $thh);
		if(move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/'.$dir.'/'.$_FILES['file']['name']))
		{
			imagecopyresampled($image_th, $image, 0, 0, 0, 0, $thw, $thh, imagesx($image), imagesy($image));
			imagejpeg($image_th, 'uploads/'.$dir.'/th/'.$_FILES['file']['name']);
		}
		else
		{
			// TODO : put real error message
			die('error while uploading the picture');
		}
		
		$req = $this->db->prepare('INSERT INTO `images`(`image`, `dossier`, `user_id`, `created`, `width`, `height`) VALUES(?, ?, ?, NOW(), ?, ?)');
		$req->execute(array(
			$_FILES['file']['name'],
			$dir,
			$this->user['id'],
			0,
			0
		));
		$req->closeCursor();
		$last_insert_id = $this->db->lastInsertId();
		
		// tags
		$tags = explode(' ', $_POST['tags']);
		foreach($tags AS $tag)
		{
			$req = $this->db->prepare('INSERT INTO `tags` (`id`, `name`, `type_id`, `count`) VALUES (NULL, ?, 1, 1) ON DUPLICATE KEY UPDATE `count` = `count` + 1');
			$req->execute(array($tag));
			$req->closeCursor();
			$tag_last_id = $this->db->lastInsertId();
			
			$req = $this->db->prepare('INSERT INTO `images_tags` (`id`, `tag_id`, `image_id`) VALUES (NULL, ?, ?)');
			$req->execute(array($tag_last_id, $last_insert_id));
			$req->closeCursor();
		}
		
		$this->tpl->set('REDIRECT_TIME', 10);
		$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post/'.$last_insert_id);
		$this->tpl->set('FLASH_MESSAGE', 'Image uploaded.');
		$this->tpl->set('MODULE', 'flash.html');
	}
}
?>