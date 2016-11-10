<?php

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class DirectEnergie extends eqLogic {
    /*     * *************************Attributs****************************** */
	/*     * ***********************Methode static*************************** */
	/*     * *********************Methode d'instance************************* */
   	public function preUpdate() {
    	}  
    	public function preInsert() {
	}    
    	public function postSave() {
		self::AddCmd($this,'Relevé de compteur','Ereleve',"action", 'default');
	}	
	public function Ereleve($value,$compteurId) 	{
		$fields = array(
			'tx_degcecfluid_pi1[autoReleveElec][releveElecHp]' => $value,
			'tx_degcecfluid_pi1[autoReleveElec][optinElec]' => true,
			'tx_degcecfluid_pi1[pds]' => "",
			'tx_degcecfluid_pi1[type]' => "",
			'tx_degcecfluid_pi1[autoReleveElec][parMultisite]' => false,
			'tx_degcecfluid_pi1[autoReleveElec][validType]' => "valid_elec",
			'tx_degcecfluid_pi1[autoReleveElec][podElec]' => $compteurId,
			'tx_degcecfluid_pi1[autoReleveElec][forceElec]' => "",
			'tx_degcecfluid_pi1[autoReleveElec][typeElec]' => "BA",
			'tx_degcecfluid_pi1[mdp_mem]' => false
		);
		$url="https://clients.direct-energie.com/ma-conso/faire-mon-e-releve/";
		$result= self::SendRequet($url,$fields);
	}
	public function MonCompte(){
		$fields = array(
			'tx_deauthentification[form_valid]' => "1",
			'tx_deauthentification[redirect_url]' => "",
			'tx_deauthentification[login]' => config::byKey('login', 'DirectEnergie'),
			'tx_deauthentification[password]' => config::byKey('password', 'DirectEnergie'),
			'tx_deauthentification[mdp_oublie]' => 'Je me connecte'
		);
		$url="https://particuliers.direct-energie.com/mon-espace-client/";
		self::SendRequet($url,$fields);
	}
	public function SendRequet($url,$fields)	{
		$cookie = '/tmp/cookiesDirectEnergie.txt';
		log::add('DirectEnergie','debug',"Connextion a: ".$url);
		$postvars = '';
		foreach($fields as $key=>$value) {
			$postvars .= $key . "=" . $value . "&";
		}
		log::add('DirectEnergie','debug',"Envoie de: ".$postvars);
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
		curl_setopt ($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $postvars);
		curl_setopt ($ch, CURLOPT_POST, 1);
		$response = curl_exec($ch);
		curl_close ($ch);
		return $response;
	}
	public static function AddCmd($Equipement,$Name,$_logicalId,$Type="info", $SubType='') 	{
		$Commande = $Equipement->getCmd(null,$_logicalId);
		if (!is_object($Commande)){
			$Commande = new DirectEnergieCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($Equipement->getId());
			$Commande->setIsVisible(1);
			$Commande->setType($Type);
			$Commande->setSubType($SubType);
			$Commande->save();
		}
		return $Commande;
	}
}

class DirectEnergieCmd extends cmd {
    public function execute($_options = null) {
		$compteur=cmd::byId(str_replace('#','',$this->getEqLogic()->getConfiguration('compteur')));
		if(is_object($compteur)){
			$this->getEqLogic()->MonCompte();
			$this->getEqLogic()->Ereleve(round($compteur->execCmd()/1000),$compteur->getEqLogic()->getLogicalId());
		}
    }
}
?>
