<?php
require('config.php');
require('Talus_TPL/Talus_TPL.php');

//mb_internal_encoding('utf-8');

try {
	$db = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'], $config['user'], $config['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
} catch(Exception $e) {
	die('erreur BDD');
}

$tpl = new Talus_TPL(__DIR__.'/views/', __DIR__.'/cache/');

$tpl->set(array(
	'TITLE' => $config['title'],
	'ROOT_PATH' => $config['root_path'],
	//
	'IN_ACCOUNT' => false,
	'IN_POST' => false,
	'IN_FAQ' => false,
));

$modules = array(
					'index' => array('Index', false),
					'error' => array('Error', true),
					'post' => array('Post', true),
					'search' => array('Search', true),
				);

$removedPath = trim(substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($config['root_path'])), '/');
$requestURI = array();
if($removedPath != '')
{
	$requestURI = explode('/', $removedPath);
	if(isset($modules[$requestURI[0]]))
	{
		$module = $modules[$requestURI[0]][0];
		$is_include = $modules[$requestURI[0]][1];
		array_shift($requestURI);
	}
	else
	{
		$module = 'Error';
		$requestURI = array('02');
		$is_include = true;
	}
}
else
{
	$module = 'Index';
	$is_include = false;
}

require('modules/class.Module.php');
require('modules/class.'.$module.'.php');
$mod = new $module;
$mod->tpl = &$tpl;
$mod->db = &$db;
$mod->config = &$config;
$mod->run($requestURI);

if($is_include)
	$tpl->parse('layout.html');
?>