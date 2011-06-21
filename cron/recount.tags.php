<?php
require('../config.php');

try {
	$db = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'], $config['user'], $config['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
} catch(Exception $e) {
	die('erreur BDD');
}

echo $db->exec('UPDATE `tags` SET `count` = (SELECT COUNT(*) FROM `images_tags` WHERE `images_tags`.`tag_id` = `tags`.`id`)');
?>