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
		$this->hasAccess = $this->getAccess();
	}

	// TODO: should really need a better way for this. unsure how
    public function getAccess(){
		if (! isset($_SESSION['oauth_token'])) {
		    // get the request token
		    $reply = $this->cb->oauth_requestToken(array(
		        'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
		    ));

		    // store the token
		    $this->cb->setToken($reply->oauth_token, $reply->oauth_token_secret);
		    $_SESSION['oauth_token'] = $reply->oauth_token;
		    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
		    $_SESSION['oauth_verify'] = true;

		    // redirect to auth website
		    $auth_url = $this->cb->oauth_authorize();
		    header('Location: ' . $auth_url);
		    die();

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

		    // send to same URL, without oauth GET parameters
		    header('Location: ' . basename(__FILE__));
		    die();
		}
		// assign access token on each page load
		$this->cb->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		return true;
   }
}

?>