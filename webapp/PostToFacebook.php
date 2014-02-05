<?php
require_once("PostToInterface.php");
require_once( './libs/facebook/facebook.php' );
class PostToFacebook extends PostToInterface
{
	private $facebook;
	private $pages;
    public function initialise(){
		$this->name = "Facebook";

		// current necessary configs to set
		// $config = array(
		// 	'appId' => FB_APP_ID,
		// 	'secret' => FB_APP_SECRET,
		// 	'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
		// );

		$this->facebook = new Facebook($this->config);
		try{
			// if user removes app authorization
			$this->hasAccess = $this->has_permissions();
			if($this->hasAccess)
				$this->pages = $this->getPagesAndAccessTokens();
		}
		catch(Exception $err){

		}
    }
	public function postMessage($message, $serverData){
		$POST = $serverData['post'];
		$messageResponse = array(
			'STATUS' => 0
		);

	  	if(array_key_exists('code_page_id', $POST)) {
			//Post the message
			parse_str($POST['code_page_id'], $code_page_arr);
			$code = $code_page_arr['code'];
			$page_id = $code_page_arr['page_id'];
			
			// date_default_timezone_set('Asia/Brunei');
			// $new_message .= "\r\n\r\nPosted from Brunei Blood Bank Status App: " . date('d/m/Y h:i:s a', time());
			$fbMessageObj = array(
					"message" => strip_tags($message['message']), 
					"access_token" => $code,
			);
			try
			{
				$user_page_post = $this->facebook->api("/$page_id/feed", 'POST', $fbMessageObj);
				if($user_page_post && !empty($user_page_post['id'])){
					$messageResponse['STATUS'] = 200;
					$messageData = array(
						'id' => $user_page_post['id'],
						'link' => 'http://facebook.com/' . $user_page_post['id'],
					);
					$messageResponse['data'] = $messageData;					
				}
				else{
					$messageResponse['STATUS'] = 302;
				}
			}
			catch(Exception $err){
				$messageResponse['STATUS'] = 500;
				$messageResponse['data'] = array($err);
			}

		}
		return $messageResponse;
	}

    // TODO: should read a template somewhere
	function show_login() {
		$login_url = $this->facebook->getLoginUrl( array( 'scope' => implode(",",$this->permissions()) ));
		return '<a href="' . $login_url . '">Login to Facebook and Grant Necessary Permissions</a>';
	}
	// TODO: should read a template somewhere
    public function toString()
    {
    	if($this->hasAccess){
    		if($this->pages){
    			$msg = "";
    			$msg .= '<select name="code_page_id">';
				foreach($this->pages as $page) {
					$msg .= '<option value="' .
					          'code=' . urlencode($page['access_token']) . 
					          '&page_id=' . urlencode($page['id']) .
					          '">' .
					          $page['name'] .
					          '</option>' .
					          '';
				}
    			$msg .= '</select>';
    			return $msg;
    		}
    		else
    			return "No pages";
    	}
    	else{
    		return $this->show_login();
    	}
    }


    function getPagesAndAccessTokens(){
    	$user_id = @$this->facebook->getUser();
		$access_token = $this->facebook->getAccessToken();
		$user_pages = $this->facebook->api('/me/accounts', 'GET');
		/*
		sample output
		Array ( [data] => 
			Array ( 
				[0] => Array ( 
					[category] => Community 
					[name] => Test FB Development 
					[access_token] => long_acces_token_here 
					[perms] => Array ( 
						[0] => ADMINISTER 
						[1] => EDIT_PROFILE 
						[2] => CREATE_CONTENT 
						[3] => MODERATE_CONTENT 
						[4] => CREATE_ADS 
						[5] => BASIC_ADMIN ) 
					[id] => 292654834155433 
				) 
			) 
			[paging] => Array ( 
				[next] => https://graph.facebook.com/701062282/accounts?limit=5000&offset=5000&__after_id=602221966471802 
			) 
		)
		*/
		$page_list = array();
		if (null != $user_pages && array_key_exists('data', $user_pages)) {
			foreach($user_pages['data'] as $user_page_detail) {
				$page_list[] = array(
					'access_token' => $user_page_detail['access_token'],
					'id' => $user_page_detail['id'],
					'name' => $user_page_detail['name'],
				);
			}
		}
		return $page_list;
    }


    // check if current instance has access to facebook
	function has_permissions() {
    	$user_id = @$this->facebook->getUser();
    	#print_r($user_id);
    	if($user_id == null) return false;
		$permissions = $this->facebook->api("/me/permissions");
		foreach($this->permissions() as $perm){
			if( !array_key_exists($perm, $permissions['data'][0]) ) {	
				return false;
			}
		}
		return true;
	}

	// permissins needed to post
	function permissions(){
		return array('manage_pages', 'publish_stream');
	}
}

?>