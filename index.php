<?php
if(!defined('PHP_VERSION_ID')) /* for PHP < 5.2.7 */
{
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}
if(PHP_VERSION_ID < 50300)
{
	define('__DIR__', dirname(__FILE__)); /* for PHP < 5.3 */
}

session_start();

require('config.php');
require('lib/Link/Autoloader.php');
Link_Autoloader::register();

require('lib/Link/Environnement.php');

try {
	$db = new PDO('mysql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'], $config['user'], $config['password']);
	$db->exec('SET NAMES utf8');
	$db->exec("SET LC_TIME_NAMES = '".$config['date_lang']."'");
} catch(Exception $e) {
	die('erreur BDD');
}

// connect the user
$userdata = array();

if(empty($_SESSION['login']) || empty($_SESSION['password']))
{
	unset($_SESSION['login']);
	unset($_SESSION['password']);
}

if(!empty($_COOKIE['witcherize_data']))
{
	$uncrypt = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->config['secret_key'], base64_decode($_COOKIE['witcherize_data']), MCRYPT_MODE_ECB);
	list($user, $pass) = explode(':', $uncrypt);
	
	if(!empty($user) && !empty($pass))
	{
		$_SESSION['login'] = $user;
		$_SESSION['password'] = $pass;
	}
}

if(!empty($_SESSION['login']) && !empty($_SESSION['password']))
{
	$req = $db->prepare('SELECT `users`.`id`, `username`, `group_id`, `isModo`, `isAdmin` FROM `users` JOIN `groups` ON `groups`.`id` = `group_id` WHERE `username` = ? AND `password` = ? LIMIT 1');
	$req->execute(array(
		$_SESSION['login'],
		$_SESSION['password']
	));
	$userdata = $req->fetch(PDO::FETCH_ASSOC);
	$req->closeCursor();
}
// END

$tpl = new Link_Environnement(new Link_Loader_Filesystem(__DIR__.'/views'), new Link_Cache_Filesystem(__DIR__.'/cache'));
$tpl->set(array(
	'TITLE' => $config['title'],
	'ROOT_PATH' => $config['root_path'],
	//
	'IN_ACCOUNT' => false,
	'IN_POST' => false,
	'IN_HELP' => false,
	'IN_ADMIN' => false,
));

// lang
if(empty($_SESSION['language']))
{
	$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	foreach($languages AS $language)
	{
		$lang_name = $language;
		if(strpos($language, ';') !== false)
			$lang_name = strstr($language, ';', true);
		if(file_exists('lang/'.$lang_name.'.php'))
		{
			$_SESSION['language'] = $lang_name;
			break;
		}
	}
	
	if(empty($_SESSION['language']))
	{
		$_SESSION['language'] = $config['default_lang'];
	}
}

require('lang/'.$_SESSION['language'].'.php');
$tpl->set('LANG', $lang);
// END

$modules = array(
					'index' => array('Index', false),
					'error' => array('Error', true),
					'post' => array('Post', true),
					'search' => array('Search', true),
					'account' => array('Account', true),
					'help' => array('Help', true),
					'mail' => array('Mail', true),
					'image' => array('Image', true),
					'profile' => array('Profile', true),
					'admin' => array('Admin', true),
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
$mod->user = &$userdata;
$mod->lang = &$lang;
$mod->run($requestURI);

if($is_include)
	$tpl->parse('layout.html');
?>