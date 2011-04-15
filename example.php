<?php
include_once dirname(__FILE__).'/VkontakteService.php';

session_start();

define('APP_ID', ''); // Your vkontakte app id
define('APP_SECRET_KEY', '');

$oVkontakte = new VkontakteService(APP_ID, APP_SECRET_KEY, '/example.php?response=1');

if (isset($_GET['connect']))
{
	// connect to vkontakte
	$oVkontakte->setSettingsToAccess(array('notify', 'wall', 'friends', 'offline'));
	header('Location:'.$oVkontakte->getAuthorizeUrl());
	exit;


} elseif ($_GET['response'] && !$_SESSION['aAccessKey']) {

	// try to get access_token
	$aAccessKey = $oVkontakte->getAccessToken(array('code' => $_GET['code']));
	if (!$aAccessKey && $oVkontakte->lastResponseType == 'error')
	{
		print_r($oVkontakte->lastResponseErrorDesc);
	}
	
	$_SESSION['aAccessKey'] = $aAccessKey;		
	
	header('Location: /example.php');
	exit;
			
}


// if we have access_token we connect to vkontakte and try to get data
if ($_SESSION['aAccessKey'])
{
	$oVkontakte->setAccessToken($_SESSION['aAccessKey']['access_token']);
	$data = $oVkontakte->executeMethod('getProfiles', array('domains'=>'durov', 'fields'=>'photo,nickname,country,contacts'));
	
	print_r($data);
	exit;
}
?>
<!DOCTYPE html>
<html>
	<head>
    	<meta charset='utf-8'>
        <title>Vkontakte oAuth2 Exmaple</title>
	</head>
	<body>
		<h1>Connect to Vkontakte</h1>	
		
		<p><a href="/?connect">do connect</a></p>
	</body>      
</html>        
