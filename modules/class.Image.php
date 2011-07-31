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
			case 'edit':
				if(!empty($data[1]) && is_numeric($data[1]))
				{
					$this->edit_image($data[1]);
				}
				else
				{
					$this->tpl->set('REDIRECT_TIME', 5);
					$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post');
					$this->tpl->set('FLASH_MESSAGE', 'Invalid image identifier.');
					$this->tpl->set('MODULE', 'flash.html');
				}
				break;
			default:
				$this->tpl->set('REDIRECT_TIME', 5);
				$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post');
				$this->tpl->set('FLASH_MESSAGE', 'What do you want to do ?');
				$this->tpl->set('MODULE', 'flash.html');
		}
	}
	
	private function edit_image($id)
	{
		$req = $this->db->prepare('SELECT COUNT(*) FROM `images` WHERE `images`.`id` = ?');
		$req->execute(array($id));
		$image = $req->fetchColumn();
		$req->closeCursor();
		
		if($image)
		{
			$tags = explode(' ', $_POST['tags']);
			$current_tags = array();
			$req = $this->db->prepare('SELECT `name` FROM `tags` JOIN `images_tags` ON `tag_id` = `tags`.`id` WHERE `image_id` = ? ORDER BY `tags`.`name` ASC');
			$req->execute(array($id));
			while($t = $req->fetch(PDO::FETCH_ASSOC))
			{
				$current_tags[] = $t['name'];
			}
			$req->closeCursor();
			
			$t1 = array_diff($current_tags, $tags); // les tags supprimés
			$t2 = array_diff($tags, $current_tags); // les tags ajoutés
			
			$callback = function(&$item, $key)
			{
				$item = strtolower($item);
				$item = preg_replace('/[^a-z0-9_-]/', '', $item);
			};
			
			array_walk($t2, $callback);
			$t2 = array_filter($t2);
			
			if(count($t1))
			{
				$delete_tags = array();
				foreach($t1 AS $tagname)
				{
					$req = $this->db->prepare("SELECT `id` FROM `tags` WHERE `name` = ?");
					$req->execute(array($tagname));
					$delete_tags[] = $req->fetchColumn(0);
					$req->closeCursor();
				}
				
				$this->db->exec("UPDATE `tags` WHERE `count` = `count` - 1 WHERE `tag_id` IN(".implode(', ', $delete_tags).")");
				$this->db->exec("DELETE FROM `images_tags` WHERE `image_id` = ".intval($id)." AND `tag_id` IN(".implode(', ', $delete_tags).")");
			}
			
			if(count($t2))
			{
				foreach($t2 AS $tagname)
				{
					$this->db->exec("INSERT INTO `tags` (`name`, `type_id`, `count`) VALUES ('".$tagname."', 1, 1) ON DUPLICATE KEY UPDATE `count` = `count`+1");
					
					$req = $this->db->prepare("SELECT `id` FROM `tags` WHERE `name` = ?");
					$req->execute(array($tagname));
					$tagid = $req->fetchColumn(0);
					$req->closeCursor();
					
					$req = $this->db->prepare('INSERT INTO `images_tags` (`tag_id`, `image_id`) VALUES (?, ?)');
					$req->execute(array($tagid, $id));
					$req->closeCursor();
				}
			}
			
			$this->tpl->set('REDIRECT_TIME', 5);
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post/');
			$this->tpl->set('FLASH_MESSAGE', 'Edition complete.');
			$this->tpl->set('MODULE', 'flash.html');
		}
		else
		{
			$this->tpl->set('REDIRECT_TIME', 5);
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post');
			$this->tpl->set('FLASH_MESSAGE', 'You can\'t edit an image that doesn\'t exists.');
			$this->tpl->set('MODULE', 'flash.html');
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
		
		$md5file = md5_file($_FILES['file']['tmp_name']);
		$extension = substr($_FILES['file']['name'], strrpos($_FILES['file']['name'], '.'));
		
		$dir = substr($md5file, 0, 2);
		if(!is_dir('uploads/'.$dir))
		{
			mkdir('uploads/'.$dir.'/th', 0777, true);
		}
		
		if(is_file('uploads/'.$dir.'/'.$md5file.$extension))
		{
			// todo : better error message
			die('file already existing');
		}
		
		$image = $this->create_image($_FILES['file']['tmp_name'], $_FILES['file']['type']);
		if($image === false)
		{
			// todo : better error message
			die('error while reading the file or incorrect format.');
		}
		
		$iw = imagesx($image);
		$ih = imagesy($image);
		// calcul thumb's dimensions
		if($iw > 150 && $ih > 150)
		{
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
		}
		else
		{
			$thh = $ih;
			$thw = $iw;
		}
		// END
		
		$image_th = imagecreatetruecolor($thw, $thh);
		if(move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/'.$dir.'/'.$md5file.$extension))
		{
			imagecopyresampled($image_th, $image, 0, 0, 0, 0, $thw, $thh, imagesx($image), imagesy($image));
			imagejpeg($image_th, 'uploads/'.$dir.'/th/'.$md5file.$extension);
		}
		else
		{
			// TODO : put real error message
			die('error while uploading the picture');
		}
		
		$req = $this->db->prepare('INSERT INTO `images`(`image`, `dossier`, `user_id`, `created`, `width`, `height`) VALUES(?, ?, ?, NOW(), ?, ?)');
		$req->execute(array(
			$md5file.$extension,
			$dir,
			$this->user['id'],
			$iw,
			$ih
		));
		$req->closeCursor();
		$last_insert_id = $this->db->lastInsertId();
		
		// tags
		$tags = explode(' ', $_POST['tags']);
		foreach($tags AS $tag)
		{
			if(!empty($tag))
			{
				$req = $this->db->prepare('INSERT INTO `tags` (`id`, `name`, `type_id`, `count`) VALUES (NULL, ?, 1, 1) ON DUPLICATE KEY UPDATE `count` = `count` + 1');
				$req->execute(array($tag));
				$req->closeCursor();
				$tag_last_id = $this->db->lastInsertId();
				
				$req = $this->db->prepare('INSERT INTO `images_tags` (`tag_id`, `image_id`) VALUES (NULL, ?, ?)');
				$req->execute(array($tag_last_id, $last_insert_id));
				$req->closeCursor();
			}
		}
		
		$this->tpl->set('REDIRECT_TIME', 10);
		$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post/'.$last_insert_id);
		$this->tpl->set('FLASH_MESSAGE', 'Image uploaded.');
		$this->tpl->set('MODULE', 'flash.html');
	}
	
	private function create_image($file, $type)
	{
		$image = false;
		
		switch($type)
		{
			case 'image/jpeg':
				$image = imagecreatefromjpeg($_FILES['file']['tmp_name']);
				break;
			case 'image/png':
				$image = imagecreatefrompng($_FILES['file']['tmp_name']);
				break;
			case 'image/gif':
				$image = imagecreatefromgif($_FILES['file']['tmp_name']);
				break;
		}
		
		return $image;
	}
}
?>