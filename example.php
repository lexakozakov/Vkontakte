<?php

include_once dirname(__FILE__).'/VkontakteService.php';

session_start();

define('APP_ID', 111111); // Your vkontakte app id
define('APP_SECRET_KEY', '*********');


$oVkontakte = new VkontakteService(APP_ID, APP_SECRET_KEY, '/?response=1');



if ($_GET['response'] && !$_SESSION['aAccessKey'])
{

	// try to get access_token
	$aAccessKey = $oVkontakte->getAccessToken(array('code' => $_GET['code']));
	if (!$aAccessKey && $oVkontakte->lastResponseType == 'error')
	{
		var_dump($oVkontakte->lastResponseErrorDesc);
	}
	
	$_SESSION['aAccessKey'] = $aAccessKey;		
	
	header('Location: /');
	exit;
			
}else{
	// connect to vkontakte
	$oVkontakte->setSettingsToAccess(array('notify', 'wall', 'friends', 'offline'));
	header('Location:'.$oVkontakte->getAuthorizeUrl());
	exit;
}


// if we have access_token we connect to vkontakte and try to get data
if ($_SESSION['aAccessKey'])
{
	$oVkontakte->setAccessToken($_SESSION['aAccessKey']);
	$data = $oVkontakte->executeMethod('getProfiles', array('domains'=>'durov', 'fields'=>'photo,nickname,country,contacts'));
	
	var_dump($data);
}
