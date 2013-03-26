<?php

error_reporting(E_ALL&~E_NOTICE&~E_DEPRECATED);

// include vkontakte library
include_once dirname(__FILE__).'/lib/VkontakteService/VkontakteService.php';

session_start();

define('APP_ID', ''); // vkontakte app id
define('APP_SECRET_KEY', ''); // vkontakte app secret

// create object
$oVkontakte = new VkontakteService(APP_ID, APP_SECRET_KEY, 'http://'.$_SERVER['HTTP_HOST'].'/?response=1');


if (isset($_GET['connect']))
{
	// connect to vkontakte
	$oVkontakte->setSettingsToAccess(array('notify', 'wall', 'friends', 'offline'));
	header('Location:'.$oVkontakte->getAuthorizeUrl());
	exit;
	
} elseif(isset($_GET['logout'])) {
    
    $_SESSION['aAccessKey'] = '';		
    header('Location: /');
    exit;

} elseif ($_GET['response'] && !$_SESSION['aAccessKey']) {

	// try to get access_token
	$aAccessKey = $oVkontakte->getAccessToken(array('code' => $_GET['code']));
	if (!$aAccessKey && $oVkontakte->lastResponseType == 'error')
	{
		$connectionErrors = $oVkontakte->lastResponseErrorDesc;
	}else{
    	$_SESSION['aAccessKey'] = $aAccessKey;		
    	header('Location: /');
    	exit;
	}
}

// if we have access_token we try to connect to vkontakte and fetch data
if ($_SESSION['aAccessKey'])
{
	$oVkontakte->setAccessToken($_SESSION['aAccessKey']['access_token']);
	$aLoggedUser = $oVkontakte->executeMethod('getProfiles', array('fields'=>'photo,nickname,country,contacts'));
	if ($aLoggedUser) $aLoggedUser = json_decode($aLoggedUser);
}

include dirname(__FILE__).'/template/home.html';
