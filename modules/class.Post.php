<?php
class Post extends Module
{
	public function run($data)
	{
		parent::run($data);
		
		$this->tpl->set('IN_POST', true);
		$this->tpl->set('SEARCH', '');
		$this->tpl->set('MODULE', 'post.html');
		
		$type = count($data) ? $data[0] : '';
		if($type == 'search')
		{
			if(count($data) > 1)
				$this->search($data);
			else
			{
				header('location:'.$this->config['root_path'].'/error/03');
				exit; /* useless but it just case */
			}
		}
		else if(is_numeric($type))
		{
			$this->show($type);
		}
		else // it's a keywork
		{
			$this->listall($data);
		}
	}
	
	private function listall($values)
	{
		$cntv = count($values);
		$word = ($cntv > 0 ? $values[0] : 'list');
		$page = ($cntv > 1 ? intval($values[1]) : 1);
		
		$first = abs($page - 1) * $this->config['ipp'];
		// FETCH
		if($word == 'list')
		{
			$req = $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS `id`, `dossier`, `image` FROM `images` ORDER BY `created` DESC, `id` DESC LIMIT ?, ?');
			$req->bindParam(1, $first, PDO::PARAM_INT);
			$req->bindParam(2, $this->config['ipp'], PDO::PARAM_INT);
			$req->execute();
		}
		else
		{
			$req = $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS `images`.`id`, `dossier`, `image` FROM `images` JOIN `images_tags` ON `image_id` = `images`.`id` JOIN `tags` ON `tag_id` = `tags`.`id` WHERE `tags`.`name` = ? ORDER BY `created` DESC, `images`.`id` DESC LIMIT ?, ?');
			$req->bindParam(1, $word, PDO::PARAM_STR);
			$req->bindParam(2, $first, PDO::PARAM_INT);
			$req->bindParam(3, $this->config['ipp'], PDO::PARAM_INT);
			$req->execute();
			
			$this->tpl->set('SEARCH', $word);
		}
		$images_id = array();
		$images = array();
		while($fetched = $req->fetch(PDO::FETCH_ASSOC))
		{
			$images_id[] = $fetched['id'];
			$images[] = $fetched;
		}
		$req->closeCursor();
		
		$req = $this->db->query('SELECT FOUND_ROWS()');
		$total_images = $req->fetchColumn(0);
		$req->closeCursor();
		// END
		
		if(count($images_id))
		{
			$req = $this->db->query('SELECT DISTINCT(`tags`.`name`), `count`, `color` FROM `tags` JOIN `types` ON `types`.`id` = `type_id` JOIN `images_tags` ON `tag_id` = `tags`.`id` WHERE `image_id` IN ('.implode(', ', $images_id).') ORDER BY `tags`.`name` ASC LIMIT 20');
			$tags = $req->fetchAll(PDO::FETCH_ASSOC);
			$req->closeCursor();
		}
		else
			$tags = array();
		
		$nb_pages = ceil($total_images / $this->config['ipp']);
		
		$this->tpl->set('IMAGES', $images);
		$this->tpl->set('TAGS', $tags);
		$this->tpl->set('PAGINATION', $this->generate_pagination($page, $nb_pages, 'post/'.$word));
		
		if($cntv && $values[0] !== 'list')
			$this->tpl->set('SUB_TITLE', $values[0]);
	}
	
	private function show($id)
	{
		$req = $this->db->prepare('SELECT `images`.`id`, `height`, `width`, `dossier`, `image`, `has_sample`, `username`, DATE_FORMAT(`created`, \''.$this->config['date_format'].'\') AS `created` FROM `images` JOIN `users` ON `user_id` = `users`.`id` WHERE `images`.`id` = ?');
		$req->execute(array($id));
		$image = $req->fetch(PDO::FETCH_ASSOC);
		$req->closeCursor();
		
		$req = $this->db->prepare('SELECT `tags`.`name`, `count`, `color` FROM `tags` JOIN `types` ON `types`.`id` = `type_id` JOIN `images_tags` ON `tag_id` = `tags`.`id` WHERE `image_id` = ? ORDER BY `tags`.`name` ASC');
		$req->execute(array($id));
		$tags = $req->fetchAll(PDO::FETCH_ASSOC);
		$req->closeCursor();
		
		$tag_list = array();
		foreach($tags AS $tag)
		{
			$tag_list[] = $tag['name'];
		}
		
		$this->tpl->set('IMAGEDATA', $image);
		$this->tpl->set('TAGS', $tags);
		$this->tpl->set('TAGS_LIST', implode(' ', $tag_list));
	}
	
	private function search($values)
	{
		$cntv = count($values);
		$page = ($cntv > 2 ? intval($values[2]) : 1);
		$first = abs($page - 1) * $this->config['ipp'];
		
		// creating the condition
		$condition = array();
		$tags_jointures = '';
		$tag_jointure_id = 1;
		foreach(explode('+', $values[1]) AS $keyword)
		{
			$field = '`tags`.`name`';
			$operator = '=';
			if(strpos($keyword, ':') !== false)
			{
				$this->parse_special_keyword($field, $keyword, $operator);
			}
			
			if($field == '`tags`.`name`')
			{
				$field = str_replace('`tags`', '`tags_'.$tag_jointure_id.'`', $field);
				$tags_jointures .= 'JOIN `images_tags` `images_tags_'.$tag_jointure_id.'` ON `images`.`id` = `images_tags_'.$tag_jointure_id.'`.`image_id`
									JOIN `tags` AS `tags_'.$tag_jointure_id.'` ON `images_tags_'.$tag_jointure_id.'`.`tag_id` = `tags_'.$tag_jointure_id.'`.`id` ';
				$tag_jointure_id++;
			}
			
			if(strpos($keyword, '*') !== false)
			{
				$condition[] = $field.' LIKE '.$this->db->quote(str_replace('*', '%', $keyword), PDO::PARAM_STR);
			}
			else
			{
				$condition[] = $field.' '.$operator.' '.$this->db->quote($keyword, PDO::PARAM_STR);
			}
		}
		
		if($tag_jointure_id > 4) // avoid mega complex query (4-1 so 3 keywords)
		{
			$this->tpl->set('FLASH_MESSAGE', 'Maximum of 3 keywords.');
			$this->tpl->set('REDIRECT_URL', $this->config['root_path'].'/post');
			$this->tpl->set('REDIRECT_TIME', 10);
			$this->tpl->set('MODULE', 'flash.html');
			return;
		}
		
		// prepare and execute the query
		$req = $this->db->prepare('SELECT SQL_CALC_FOUND_ROWS DISTINCT(`images`.`id`), `dossier`, `image` FROM `images`
									'.$tags_jointures.'
									JOIN `users` ON `user_id` = `users`.`id`
								WHERE '.implode(' AND ', $condition).' ORDER BY `images`.`created` DESC, `images`.`id` DESC LIMIT ?, ?');
		$req->bindParam(1, $first, PDO::PARAM_INT);
		$req->bindParam(2, $this->config['ipp'], PDO::PARAM_INT);
		$req->execute();
		
		// fetch the data
		$images_id = array();
		$images = array();
		while($fetched = $req->fetch(PDO::FETCH_ASSOC))
		{
			$images_id[] = $fetched['id'];
			$images[] = $fetched;
		}
		$req->closeCursor();
		
		// number of rows
		$req = $this->db->query('SELECT FOUND_ROWS()');
		$total_images = $req->fetchColumn(0);
		$req->closeCursor();
		
		$nb_pages = ceil($total_images / $this->config['ipp']);
		
		$tags = array();
		if(count($images_id))
		{
			// get the tags if pictures found
			$req = $this->db->query('SELECT DISTINCT(`tags`.`name`), `count`, `color` FROM `tags` JOIN `types` ON `types`.`id` = `type_id` JOIN `images_tags` ON `tag_id` = `tags`.`id` WHERE `image_id` IN ('.implode(', ', $images_id).') ORDER BY `count` DESC, `tags`.`name` ASC LIMIT 20');
			$tags = $req->fetchAll(PDO::FETCH_ASSOC);
			$req->closeCursor();
		}
		
		$this->tpl->set('IMAGES', $images);
		$this->tpl->set('TAGS', $tags);
		$this->tpl->set('SEARCH', str_replace('+', ' ', $values[1]));
		$this->tpl->set('PAGINATION', $this->generate_pagination($page, $nb_pages, 'post/search/'.$values[1]));
	}
	
	private function parse_special_keyword(&$field, &$keyword, &$operator)
	{
		$keydata = explode(':', $keyword, 2);
		switch($keydata[0])
		{
			case 'user':
				$field = '`users`.`username`';
				$keyword = $keydata[1];
				break;
			case 'height':
				$field = '`images`.`height`';
				$keyword = $this->select_operator($keydata[1], $operator);
				break;
			case 'width':
				$field = '`images`.`width`';
				$keyword = $this->select_operator($keydata[1], $operator);
				break;
			default:
				;
		}
	}
	
	private function select_operator($keyword, &$operator)
	{
		$op = strspn($keyword, '<>=');
		$operator = '';
		if($op == 1)
			$operator = $keyword[0];
		elseif($op == 2)
			$operator = $keyword[0].$keyword[1];
		switch($operator)
		{
			case '<':
			case '>':
			case '<=':
			case '>=':
				break;
			default: // bad operators are converted to '='
				$operator = '=';
		}
		return ltrim($keyword, '<>=');
	}
	
	private function generate_pagination($page, $nb_page, $url)
	{
		$nb = 3;
		$list_page = array();
		
		if($page > 1)
		{
			$list_page[] = '<span><a href="'.$this->config['root_path'].'/'.$url.'/'.($page-1).'" title="">&lt;</a></span>';
		}
		
		for($i = 1; $i <= $nb_page; $i++)
		{
			if(($i < $nb + 1) || ($i > $nb_page - $nb) || (($i < $page + $nb) && ($i > $page - $nb)))
			{
				$list_page[] = '<span'.($i == $page ? ' class="active"' : '').'><a href="'.$this->config['root_path'].'/'.$url.'/'.$i.'" title="">'.$i.'</a></span>';
			}
			elseif($i >= $nb && $i <= $page - $nb)
			{
				$i = $page - $nb;
				$list_page[] = '<span>...</span>';
			}
			elseif($i >= $page + $nb && $i <= $nb_page - $nb)
			{
				$i = $nb_page - $nb;
				$list_page[] = '<span>...</span>';
			}
		}
		
		if($page < $nb_page)
		{
			$list_page[] = '<span><a href="'.$this->config['root_path'].'/'.$url.'/'.($page+1).'" title="">&gt;</a></span>';
		}
		
		return $list_page;
	}
}
?>