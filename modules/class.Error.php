<?php
class Error extends Module
{
	private $messages = array();
	
	public function __construct()
	{
		$this->messages['00'] = 'Erreur inconnue';
		$this->messages['01'] = 'Cette image n\'existe pas';
		$this->messages['02'] = 'Ce module n\'existe pas';
		$this->messages['03'] = 'URL de recherche mal-formée trop (ou pas assez) d\'argument(s).';
	}
	
	public function run($data)
	{
		$this->tpl->set('SUB_TITLE', 'ERROR');
		$this->tpl->set('ERR_MSG', (count($data) && isset($this->messages[$data[0]]) ? $this->messages[$data[0]] : $this->messages['00']));
		$this->tpl->set('MODULE', 'error.html');
	}
}
?>