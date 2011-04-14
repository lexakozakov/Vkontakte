<?php

/**
 * Vkontakte API wrapper with support for authentication using OAuth 2.
 *
 * @category Services
 * @package VkontakteService
 * @author Lexa Kozakov <lexakozakov@gmail.com>
 * @copyright 2011 Lexa kozakov <lexakozakov@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link http://github.com/mptre/php-soundcloud
 * @link https://github.com/lexakozakov/Vkontakte
 */

class VkontakteService
{
    /**
	* Class version.
	*
	* @var int
	*/
    const MAJOR = 1;
    const MINOR = 0;
    const PATCH = 0;
	
	
	/**
	* Login Type to Vkontakte.
	*
	* @var string, can be 'client' or 'server'
	*/
	private $loginType = 'server';


	/**
	* Class version.
	*
	* @var string
	*/
	public $version;


    /**
	* HTTP user agent.
	*
	* @access private
	*
	* @var string
	*/
	private static $_userAgent = 'PHP-Vkontakte';
    
    
    /**
	* Vkontakte App Id.
	*
	* @access private
	*
	* @var int
	*/
	private $iAppId;


    /**
	* Vkontakte App Secret Key.
	*
	* @access private
	*
	* @var string
	*/
	private $sAppSecret;


    /**
	* Redirect Url While Authorization.
	*
	* @access private
	*
	* @var string
	*/
	private $sRedirectUrl;


    /**
	* Access Token.
	*
	* @access private
	*
	* @var string
	*/
	private $sAccessToken;
	
	
    /**
	* Vkontakte Api Host for oAuth authorization and Api function execution.
	*
	* @access private
	*
	* @var string
	*/
	private $sHost = 'api.vkontakte.ru';
	
	
    /**
	* Vkontakte's Allowed Setting.
	*
	* @access private
	*
	* @var array
	*/
	private $aAllowedSettings = array(
		'notify',		// Пользователь разрешил отправлять ему уведомления.
		'friends',		// Доступ к друзьям.
		'photos',		// Доступ к фотографиям.
		'audio',		// Доступ к аудиозаписям.
		'video',		// Доступ к видеозаписям.
		'notes',		// Доступ заметкам пользователя.
		'pages',		// Доступ к wiki-страницам.
		'offers',		// Доступ к предложениям (устаревшие методы).
		'questions',	// Доступ к вопросам (устаревшие методы).
		'wall',			// Доступ к обычным и расширенным методам работы со стеной.
		'messages',		// (для Standalone-приложений) Доступ к расширенным методам работы с сообщениями.
		'offline',		// Доступ к API в любое время со стороннего сервера.  
	);
	

    /**
	* Vkontakte's Allowed Setting which we would like to get.
	*
	* @access protected
	*
	* @var array
	*/
	protected $aSettingsToAccess;
	
	
    /**
	* Vkontakte's Allowed Setting.
	*
	* @access private
	*
	* @var array
	*/
	private $returnFormat = 'json'; // can only 'xml' or 'json'
	
	
	function __construct($iAppId, $sAppSecret, $sRedirectUrl = null)
	{
		$this->setAppID($iAppId);
		$this->setAppSecret($sAppSecret);
		$this->setRedirectUrl($sRedirectUrl);

        $this->version = $this->getVersion();
	}

	
	/**
	* This function return url for getting access to vkontakte's services 	
	*	
	* @param $params, 
	*
	* @return string
	*/
	public function getAuthorizeUrl($params = array())
	{
		$params = array_merge(array(
			'url_type' => 'authorize',
			'client_id' => $this->iAppId,
			'redirect_uri' => urlencode($this->sRedirectUrl),
			'response_type' => ($this->loginType == 'client') ? 'token' : 'code',
			'scope' => $this->getSettingsToAccess(),
		), $params);
		return $this->buildUrl($params);
	}
	

	/**
	* This function return url for getting Vkontake's Auth Token 
	*	
	* @param $params 
	*
	* @return string
	*/
	public function getAccessTokenUrl($params = array())
	{
		$params = array_merge(array(
			'url_type' => 'access_token',
			'client_id' => $this->iAppId,
			'client_secret' => $this->sAppSecret,
			'code' => $params['code']
		), $params);
		
		return $this->buildUrl($params, true);
	}
	
    
    /**
     * Retrieve access token.
     *
     * @param array $getData
     * @param array $curlOptions Optional cURL options
     *
     * @return mixed
     */
	public function getAccessToken($getData, $curlOptions = array())
	{
        $response = json_decode( $this->request($this->getAccessTokenUrl($getData), $curlOptions), true );

        if (is_array($response) && array_key_exists('access_token', $response)) {
            $this->sAccessToken = $response['access_token'];

            return $response;
        } else {
        	$this->lastResponseType = is_array($response) && array_key_exists('error', $response) ? 'error' : '';
        	$this->lastResponseError = $response['error'];
        	$this->lastResponseErrorDesc = $response['error_description'];
        	$this->lastResponse = $response;
            return false;
        }
	}
	
	
    /**
     * Set access token.
     *
     * @param string $sAccessToken
     *
     * @return true
     */
    function setAccessToken($sAccessToken) 
    {
        $this->sAccessToken = $sAccessToken;
        return true;
    }
	
	
    /**
     * Set settings which we would like to get access.
     *
     * @param array $aSettings
     *
     * @return true
     */
	public function setSettingsToAccess($aSettings)
	{
		if (!is_array($aSettings) || empty($aSettings)) trigger_error('You must set a one allowed setting at least.');
		if (!is_array($this->aAllowedSettings) || empty($this->aAllowedSettings)) trigger_error('Allowed settings can not be empty.');
		
		foreach($aSettings as $key => $val)
		{
			if (in_array($val, $this->aAllowedSettings)) $this->aSettingsToAccess[] = $val;
		}
		
		return true;
	}
	
	
    /**
     * Get settings which we would like to get access.
     *
     * @param array $aSettings
     *
     * @return string
     */
	public function getSettingsToAccess()
	{
		return is_array($this->aSettingsToAccess) ? implode(',', $this->aSettingsToAccess) : '';
	}
	
	
    /**
     * Set access token.
     *
     * @param string $sMethodName
     * @param array $aParams
     * @param array $curlOptions
     *
     * @return string or array()
     */
	public function executeMethod($sMethodName, $aParams = array(), $curlOptions = array())
	{
		
		$params = array(
			'method' => $sMethodName.($this->returnFormat == 'xml' ? '.xml' : ''),
			
		);
		$params += $aParams;
		$url = $this->buildUrl($params, true);
		$response = $this->request($url, $curlOptions);

		if ($this->returnFormat =='xml')
		{
			$ret = $response;
		}else{
			$aResponse = json_decode($response, true);

        	$this->lastResponseType = is_array($aResponse) && array_key_exists('error', $aResponse) ? 'error' : '';

			if (is_array($aResponse) && array_key_exists('error', $aResponse))
			{
        		$this->lastResponseError = $response['error']['error_code'];
        		$this->lastResponseErrorDesc = $response['error']['error_msg'];
        		$this->lastResponse = $response['error'];
				$ret = array();
			}else{
				$ret = $response['response'];
			}
		
		}
		
		
		return $response;  
	}
	
	
    /**
     * Make Vkontake URL for authorization and to execute vkontake's api functions 
     *
     * @param array $params
     * @param bollean $https
     *
     * @return string 
     */
	protected function buildUrl($params, $https = false)
	{
		$sUrl = 'http'.( $https ? 's' : '' ).'://' . $this->sHost.'/'.($params['method'] ? 'method' : 'oauth').'/'.($params['method'] ? $params['method'] : $params['url_type']).'?';
		
		$getParams =  $params;
		unset($getParams['url_type'], $getParams['method']);
		
		if (is_array($getParams) && !empty($getParams))
		{
			foreach($getParams as $key => $val)
			{
				$sUrl .= $key.'='.$val.'&';
			}
			$sUrl = substr($sUrl, 0, -1);
		}

		if ($params['method']) $sUrl .= '&access_token='.$this->sAccessToken;
		return $sUrl;
	}


    /**
     * Set Vkontakte Application ID 
     *
     * @param int $iAppId
     *
     * @return  
     */
	public function setAppID($iAppId)
	{
		$this->iAppId = (int)$iAppId;
	}


    /**
     * Set Vkontakte Secret Key 
     *
     * @param string $sAppSecret
     *
     * @return  
     */
	public function setAppSecret($sAppSecret)
	{
		$this->sAppSecret = trim($sAppSecret);
	}

	
    /**
     * Set Redirect Url (for Authorization) 
     *
     * @param string sRedirectUrl
     *
     * @return  
     */
	public function setRedirectUrl($sRedirectUrl)
	{
		$this->sRedirectUrl = $sRedirectUrl;
	}


    /**
     * Get HTTP user agent.
     *
     * @access protected
     *
     * @return string
     */
    protected function getUserAgent() 
    {
        return self::$_userAgent . '/' . $this->version;
    }


    /**
     * Execute curl request to server
     *
     * @param string $url
     * @param array $curlOptions
     *
     * @return string 
     */
    protected function request($url, $curlOptions = array()) 
    {
        
        $ch = curl_init();
        
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $this->getUserAgent()
        );
        $options += $curlOptions;
		

		if (preg_match("/https.*$/", $url))
		{
			$options += array(
            	CURLOPT_SSL_VERIFYPEER => false,
            	CURLOPT_SSL_VERIFYHOST => false,
			);		
		}
		

        curl_setopt_array($ch, $options);

        $data = curl_exec($ch);
        $info = curl_getinfo($ch);

        return $data;
    }


    /**
     * Get version of this class
     *
     * @return string 
     */
	private function getVersion()
	{
        return implode('.', array(self::MAJOR, self::MINOR, self::PATCH));
	} 

}

?>