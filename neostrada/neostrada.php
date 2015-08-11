<?php
/**
 * Copyright (c) 2014, Avot Media BV
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @license     Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
 * @author      Avot Media BV <api@neostrada.nl>
 * @copyright   Avot Media BV
 * @link        http://www.avot.nl / http://www.neostrada.nl
 */
require_once '3rdparty/domain/IRegistrar.php';
require_once '3rdparty/domain/standardfunctions.php';
/**
 * Neostrada WeFact Hosting registrar class
 */
class Neostrada implements IRegistrar
{
	/**
	 * Defines the API host
	 *
	 * @var	string
	 */
	const API_HOST = 'https://api.neostrada.nl/';
	/**
	 * Contains the API key
	 *
	 * @var	string
	 */
	public $User;
	/**
	 * Contains the API secret
	 *
	 * @var	string
	 */
	public $Password;
	/**
	 * Contains the cURL session
	 *
	 * @var	object
	 */
	private $cURL = FALSE;
	/**
	 * Contains the URL
	 *
	 * @var	string
	 */
	private $URL = '';
	/**
	 * Contains the API request result
	 *
	 * @var	object
	 */
	private $Data = FALSE;
	/**
	 * Array with error messages
	 *
	 * @var	array
	 */
	public $Error = array();
	/**
	 * Array with warning messages
	 *
	 * @var	array
	 */
	public $Warning = array();
	/**
	 * Array with success messages
	 *
	 * @var	array
	 */
	public $Success = array();
	/**
	 * Defines the default registration period in years
	 *
	 * @var	int
	 */
	public $Period = 1;
	/**
	 * Array with register handles that are stored by WeFact
	 *
	 * @var	array
	 */
	public $registrarHandles = array();
	/**
	 * Contains the class name
	 *
	 * @var	string
	 */
	private $ClassName = __CLASS__;
	/**
	 * Neostrada :: checkDomain
	 *
	 * @return	bool
	 */
	public function checkDomain($domain)
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		$this->prepare('whois', array(
			'domain'	=> $DomainName,
			'extension'	=> $Extension
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 210 ? TRUE : FALSE);
		}
		return $RV;
	}
	/**
	 * Neostrada :: registerDomain
	 *
	 * @return	bool
	 */
	public function registerDomain($domain, $nameservers = array(), $whois = NULL)
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		if (array_key_exists($this->ClassName, $whois->ownerRegistrarHandles)) {
			$HolderID = $whois->ownerRegistrarHandles[$this->ClassName];
		} elseif (strlen($whois->ownerSurName) > 0) {
			$HolderID = $this->createContact($whois, HANDLE_OWNER);
			$this->registrarHandles['owner'] = $HolderID;
		}
		if ((int)$HolderID === 0) $HolderID = 636787232;
		$this->prepare('registerwefact', array(
			'domain'	=> $DomainName,
			'extension' => $Extension,
			'holderid'	=> $HolderID,
			'period'	=> 1,
			'webip'		=> '',
			'packageid'	=> 0,
			'ns1'		=> $nameservers['ns1'],
			'ns2'		=> $nameservers['ns2'],
			'ns3'		=> $nameservers['ns3']
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 200 ? TRUE : FALSE);
		}
		return $RV;
	}
	/**
	 * Neostrada :: transferDomain
	 *
	 * @return	bool
	 */
	public function transferDomain($domain, $nameservers = array(), $whois = NULL, $authcode = '')
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		if (array_key_exists($this->ClassName, $whois->ownerRegistrarHandles)) {
			$HolderID = $whois->ownerRegistrarHandles[$this->ClassName];
		} elseif (strlen($whois->ownerSurName) > 0) {
			$HolderID = $this->createContact($whois, HANDLE_OWNER);
			$this->registrarHandles['owner'] = $HolderID;
		}
		if ((int)$HolderID === 0) $HolderID = 636787232;
		$this->prepare('transferwefact', array(
			'domain'	=> $DomainName,
			'extension' => $Extension,
			'authcode'	=> $authcode,
			'holderid'	=> $HolderID,
			'webip'		=> '',
			'ns1'		=> $nameservers['ns1'],
			'ns2'		=> $nameservers['ns2'],
			'ns3'		=> $nameservers['ns3']
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 200 ? TRUE : FALSE);
			if ((int)$Result['code'] === 504) $this->Error[] = '[NEOSTRADA] Auth token missing';
		}
		return $RV;
	}
	/**
	 * Neostrada :: extendDomain
	 *
	 * @return	bool
	 */
	public function extendDomain($domain, $nyears)
	{
		$this->Error[] = '[NEOSTRADA] Domain extending is currently not supported';
		return FALSE;
	}
	/**
	 * Neostrada :: deleteDomain
	 *
	 * @return	bool
	 */
	public function deleteDomain($domain, $delType = 'end')
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		$this->prepare('delete', array(
			'domain'	=> $DomainName,
			'extension'	=> $Extension
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 200 ? TRUE : FALSE);
			if ((int)$Result['code'] === 504) $this->Error[] = '[NEOSTRADA] Renewal date is to close';
		}
		return $RV;
	}
	/**
	 * Neostrada :: getDomainInformation
	 *
	 * @return	bool
	 */
	public function getDomainInformation($domain)
	{
		$this->Error[] = '[NEOSTRADA] Domain information is currently not supported';
		return FALSE;
	}
	/**
	 * Neostrada :: getDomainList
	 *
	 * @return	mixed
	 */
	public function getDomainList($contactHandle = '')
	{
		$RV = FALSE;
		$this->prepare('domainslist');
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			if ((int)$Result['code'] === 200 && is_array($Result['details']) && count($Result['details']) > 0) {
				$RV = array();
				foreach ($Result['details'] AS $D) {
					list ($Domain, $StartDate, $ExpirationDate, $Nameservers, $HolderID) = explode(';', urldecode($D));
					$Whois = new whois();
					$Whois->ownerHandle = $HolderID;
					$Whois->adminHandle = $HolderID;
					$Whois->techHandle = $HolderID;
					$RV[] = array(
						'Domain'			=> $Domain,
						'Information'		=> array(
							'nameservers'	=> explode(',', $Nameservers),
							'whois'			=> $Whois,
							'expires'		=> rewrite_date_db2site($ExpirationDate),
							'regdate'		=> rewrite_date_db2site($StartDate)
						)
					);
				}
			}
		}
		if ($RV === FALSE) $this->Error[] = '[NEOSTRADA] No domains found, try again later';
		return $RV;
	}
	/**
	 * Neostrada :: lockDomain
	 *
	 * @return	bool
	 */
	public function lockDomain($domain, $lock = true)
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		$this->prepare('lock', array(
			'domain'	=> $DomainName,
			'extension'	=> $Extension,
			'lock'		=> ((bool)$lock === TRUE ? 1 : 0)
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 200 ? TRUE : FALSE);
		}
		if ($RV === FALSE) $this->Error[] = '[NEOSTRADA] Domain locking not supported for this extension';
		return $RV;
	}
	/**
	 * Neostrada :: setDomainAutoRenew
	 *
	 * @return	bool
	 */
	public function setDomainAutoRenew($domain, $autorenew = TRUE)
	{
		$this->Error[] = '[NEOSTRADA] Domain auto-renewing is currently not supported';
		return FALSE;
	}
	/**
	 * Neostrada :: updateDomainWhois
	 *
	 * @return	bool
	 */
	public function updateDomainWhois($domain, $whois)
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		if (array_key_exists($this->ClassName, $whois->ownerRegistrarHandles)) {
			$HolderID = $whois->ownerRegistrarHandles[$this->ClassName];
		} elseif (strlen($whois->ownerSurName) > 0) {
			$HolderID = $this->createContact($whois, HANDLE_OWNER);
			$this->registrarHandles['owner'] = $HolderID;
		}
		if ((int)$HolderID === 0) $HolderID = 636787232;
		$this->prepare('modify', array(
			'domain'	=> $DomainName,
			'extension' => $Extension,
			'holderid'	=> $HolderID
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 200 ? TRUE : FALSE);
		}
		return $RV;
	}
	/**
	 * Neostrada :: getDomainWhois
	 *
	 * @return	mixed
	 */
	public function getDomainWhois($domain)
	{
		$this->Error[] = '[NEOSTRADA] Domain whois information is currently not supported';
		return FALSE;
	}
	/**
	 * Neostrada :: getToken
	 *
	 * @return	mixed
	 */
	public function getToken($domain)
	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		$this->prepare('gettoken', array(
			'domain'	=> $DomainName,
			'extension'	=> $Extension
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			if ((int)$Result['code'] === 200) $RV = (strlen($Result['token']) > 0 ? $Result['token'] : FALSE);
		}
		if ($RV === FALSE) $this->Error[] = '[NEOSTRADA] Domain auth token not set or not supported';
		return $RV;
	}
	/**
	 * Neostrada :: createContact
	 *
	 * @return	bool
	 */
	public function createContact($whois, $type = HANDLE_OWNER)
	{
		$RV = FALSE;
		if (preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $whois->ownerAddress, $m) && is_array($m) && array_key_exists(1, $m) && array_key_exists(2, $m)) {
			$Country = strtolower((strlen($whois->ownerCountry) > 2 ? str_replace('EU-', '', $whois->ownerCountry) : $whois->ownerCountry));
			$this->prepare('holder', array(
				'holderid'		=> 0,
				'sex'			=> strtoupper($whois->ownerSex),
				'firstname'		=> $whois->ownerInitials,
				'center'		=> '',
				'lastname'		=> $whois->ownerSurName,
				'street'		=> $m[1],
				'housenumber'	=> $m[2],
				'hnpostfix'		=> '',
				'zipcode'		=> $whois->ownerZipCode,
				'city'			=> $whois->ownerCity,
				'country'		=> (strlen($Country) < 2 ? 'NL' : $Country),
				'email'			=> $whois->ownerEmailAddress
			));
			if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
				if ((int)$Result['code'] === 200) {
					$RV = (int)$Result['holderid'];
				} else {
					$this->Error[] = '[NEOSTRADA] Could not create contact: '.json_encode($Result);
				}
			}
		}
		return $RV;
	}
	/**
	 * Neostrada :: updateContact
	 *
	 * @return	bool
	 */
	public function updateContact($handle, $whois, $type = HANDLE_OWNER)
	{
		return $this->createContact($whois, $type);
	}
	/**
	 * Neostrada :: getContact
	 *
	 * @return	mixed
	 */
	public function getContact($handle)
	{
		global $array_country;
		$RV = new whois();
		$this->prepare('getholder', array('holderid' => $handle));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			if ((int)$Result['code'] === 200) {
				list ($HolderID, $Sex, $FirstName, $Center, $LastName, $Street, $HouseNumber, $HouseNumberPostfix, $ZIPCode, $City, $Country, $Email) = explode(';', urldecode($Result['holder']));
				$RV->ownerHandle = (int)$HolderID;
				$RV->ownerSex = strtolower($Sex);
				$RV->ownerInitials = $FirstName;
				$RV->ownerSurName = $LastName;
				$RV->ownerAddress = $Street.' '.$HouseNumber.$HouseNumberPostfix;
				$RV->ownerZipCode = $ZIPCode;
				$RV->ownerCity = $City;
				$RV->ownerCountry = (array_key_exists(strtoupper($Country), $array_country) ? strtoupper($Country) : (array_key_exists('EU-'.strtoupper($Country), $array_country) ? 'EU-'.strtoupper($Country) : ''));
				$RV->ownerEmailAddress = $Email;
			} else {
				$this->Error[] = '[NEOSTRADA] No contact found, try again later';
			}
		}
		return $RV;
	}
	/**
	 * Neostrada :: getContactHandle
	 *
	 * @return	mixed
	 */
	public function getContactHandle($whois = array(), $type = HANDLE_OWNER)
	{
		return $this->createContact($whois, $type);
	}
	/**
	 * Neostrada :: getContactList
	 *
	 * @return	array
	 */
    function getContactList($surname = '')
    {
    	global $array_country;
		$RV = FALSE;
		$this->prepare('getholders', array('holderids' => ''));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			if ((int)$Result['code'] === 200 && is_array($Result['holders']) && count($Result['holders']) > 0) {
				$RV = array();
				foreach ($Result['holders'] AS $Holder) {
					list ($HolderID, $Sex, $FirstName, $Center, $LastName, $Street, $HouseNumber, $HouseNumberPostfix, $ZIPCode, $City, $Country, $Email) = explode(';', urldecode($Holder));
					$RV[] = array(
						'Handle'		=> (int)$HolderID,
						'CompanyName'	=> '',
						'SurName'		=> (strlen($Center) > 0 ? $Center.' ' : '').$LastName,
						'Initials'		=> $FirstName,
						'Address'		=> $Street.' '.$HouseNumber.$HouseNumberPostfix,
						'ZipCode'		=> $ZIPCode,
						'City'			=> $City,
						'Country'		=> (array_key_exists(strtoupper($Country), $array_country) ? strtoupper($Country) : (array_key_exists('EU-'.strtoupper($Country), $array_country) ? 'EU-'.strtoupper($Country) : '')),
						'EmailAddress'	=> $Email
					);
				}
			}
		}
		if ($RV === FALSE) $this->Error[] = '[NEOSTRADA] No contacts found, try again later';
		return $RV;
   	}
   	/**
   	 * Neostrada :: updateNameServers
   	 *
   	 * @return	bool
   	 */
   	public function updateNameServers($domain, $nameservers = array())
   	{
		$RV = FALSE;
		list ($DomainName, $Extension) = explode ('.', $domain, 2);
		$this->prepare('nameserver', array(
			'domain'	=> $DomainName,
			'extension' => $Extension,
			'ns1'		=> $nameservers['ns1'],
			'ns2'		=> $nameservers['ns2'],
			'ns3'		=> $nameservers['ns3']
		));
		if ($this->execute() === TRUE && ($Result = $this->fetch()) !== FALSE) {
			$RV = ((int)$Result['code'] === 200 ? TRUE : FALSE);
		}
		return $RV;
	}
	/**
	 * Neostrada :: doPending
	 *
	 * @return	string
	 */
	public function doPending($domain, $pendingInfo)
	{
		return 'pending';
	}
	/**
	 * Neostrada :: getVersionInformation
	 *
	 * @return	array
	 */
	public static function getVersionInformation()
	{
		require_once '3rdparty/domain/neostrada/version.php';
		return $version;
	}
	/**
	 * Neostrada :: prepare
	 *
	 * @return	bool
	 */
	private function prepare($Action, array $Parameters = array())
	{
		$RV = FALSE;
		$this->close();
		$this->URL = '?api_key='.$this->User.'&action='.$Action.(is_array($Parameters) && count($Parameters) > 0 ? '&'.http_build_query($Parameters) : '').'&api_sig='.$this->apisignature($Action, $Parameters).'&referer=WeFact';
		if (($this->cURL = curl_init()) !== FALSE) {
			curl_setopt($this->cURL, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($this->cURL, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($this->cURL, CURLOPT_URL, Neostrada::API_HOST.$this->URL);
			curl_setopt($this->cURL, CURLOPT_HEADER, 0);
			curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, 1);
			$RV = TRUE;
		} else {
			$this->Error[] = '[NEOSTRADA] Could not connect to server, try again later';
		}
		return $RV;
	}
	/**
	 * Neostrada :: execute
	 *
	 * @return	bool
	 */
	private function execute()
	{
		$RV = FALSE;
		if ($this->cURL !== FALSE && ($this->Data = curl_exec($this->cURL)) !== FALSE) $RV = TRUE;
		$this->close();
		return $RV;
	}
	/**
	 * Neostrada :: fetch
	 *
	 * @return	mixed
	 */
	private function fetch()
	{
		$RV = FALSE;
		if ($this->Data !== FALSE && ($XML = simplexml_load_string($this->Data)) !== FALSE) {
			$RV = array();
			foreach ($XML->attributes() AS $AV) $RV[strtolower($AV->getName())] = trim((string)$AV);
			foreach ($XML->children() AS $CV) {
				if (count($CV->children()) > 0) {
					foreach ($CV->children() AS $CCV) $RV[strtolower($CV->getName())][] = trim((string)$CCV);
				} else {
					$RV[strtolower($CV->getName())] = trim((string)$CV);
				}
			}
		} else {
			$this->Error[] = '[NEOSTRADA] Could not parse result, try again later';
		}
		return $RV;
	}
	/**
	 * Neostrada :: close
	 *
	 * @return	void
	 */
	private function close()
	{
		if ($this->cURL !== FALSE) curl_close($this->cURL);
		$this->cURL = FALSE;
	}
	/**
	 * Neostrada :: apisignature
	 *
	 * @return	string
	 */
	private function apisignature($Action, array $Parameters = array())
	{
		$APISig = $this->Password.$this->User.'action'.$Action;
		foreach ($Parameters AS $Key => $Value) $APISig.= $Key.$Value;
		return md5($APISig);
	}
}
?>