<?php
require_once("PostToInterface.php");
require_once ('./libs/twitter-codebird/src/codebird.php');
class PostToTwitter extends PostToInterface
{
	private $cb;

    public function initialise(){
		$this->name = "Twitter";
    	$consumerKey = $this->config['TWITTER_CONSUMER_KEY'];
    	$consumerSecret = $this->config['TWITTER_CONSUMER_SECRET'];
    	// static, see 'Using multiple Codebird instances'
		\Codebird\Codebird::setConsumerKey($consumerKey, $consumerSecret); 

		$this->cb = \Codebird\Codebird::getInstance();
		if(@$_GET['authorize_twitter'] == 1 && @$_GET['denied'] == ""){
			$this->hasAccess = $this->getAccess();
		}
		$this->hasAccess = $this->checkPermissions();
	}
	public function postMessage($messageObj, $serverData){
		$message = $messageObj['message'];
		$attachments = $messageObj['attachments'];
		$messageResponse = array(
			'STATUS' => 0
		);
		if(!empty($message) || !empty($attachments)){
			$params = array(
				'status' => $message,
				'media[]' => !empty($attachments) ? $attachments[0] : ""
			);
			if(empty($params['media[]']))
				$reply = $this->cb->statuses_update('status=' . $message);
			else
				$reply = $this->cb->statuses_updateWithMedia($params);

			if(!empty($reply->id)){
				$post_id = $reply->id;
				$user_id = $reply->user->id;
				$user_name = $reply->user->screen_name;

				$data = array(
					'id' => $post_id,
					'link' => "https://twitter.com/$user_name/status/$post_id",
					'user_id' => $user_id,
					'user_name' => $user_name,
				);
				$messageResponse['STATUS'] = 200;
				$messageResponse['data'] = $data; // array of errors
			}
			else{
				$messageResponse['STATUS'] = 500;
				$messageResponse['data'] = $reply->errors; // array of errors
			}
		}
		return $messageResponse;
	}
	public function checkPermissions(){
		print_r($_SESSION);
		if(@$_SESSION['oauth_token'] != "" && @$_SESSION['oauth_token_secret'] != ""){
		    $this->cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		    $reply = $this->cb->account_verifyCredentials();
		    print_r($reply);
		    if($reply->httpstatus == 200){
		    	return true;
		    }
		    else{
		    	unset($_SESSION['oauth_token']);
		    	unset($_SESSION['oauth_token_secret']);
		    }
		}
		return false;
	}
	public function toString(){
		if(!$this->hasAccess){
			return '<a href="?authorize_twitter=1">Authorize Twitter</a>';
		}
	}
	// TODO: should really need a better way for this. unsure how
    public function getAccess(){
		if (!isset($_SESSION['oauth_token'])) {
		    // get the request token
		    $reply = $this->cb->oauth_requestToken(array(
		        'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
		    ));
		    if($reply->httpstatus == 200)
		    {
			    print_r($reply);
			    // store the token
			    $this->cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
			    $_SESSION['oauth_token'] = $reply->oauth_token;
			    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
			    $_SESSION['oauth_verify'] = true;

			    // redirect to auth website
			    $auth_url = $this->cb->oauth_authorize();
			    header('Location: ' . $auth_url);
			    die();
			}
			print_r($reply);
		} elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
		    // verify the token
		    $this->cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		    unset($_SESSION['oauth_verify']);

		    // get the access token
		    $reply = $this->cb->oauth_accessToken(array(
		        'oauth_verifier' => $_GET['oauth_verifier']
		    ));

		    // store the token (which is different from the request token!)
		    $_SESSION['oauth_token'] = $reply->oauth_token;
		    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
		    print_r($_SESSION);
		    die	();

		    if($this->checkPermissions()){
			    // send to same URL, without oauth GET parameters
			    header('Location: ' . basename(__FILE__));
			    die();
		    }
		}
		return false;
   }
}

?>