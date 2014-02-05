<?php
abstract class PostToInterface	
{
	protected $hasAccess = false;
	protected $name = "";
	protected $id = "";
	protected $config;

   	function __construct($config) {
   		$this->config = $config;
   		$this->initialise();
   	}

    // returns the text name of this posting class, e.g. Twitter, Facebook
    //    should be overridable in case of multiple accounts
    public function getName(){
    	return $this->name;
    } 

    // return id of html element that should be defined by caller
    public function getID(){
    	return $this->id;
    }

    // has access to post ?
    public function hasAccess(){
    	return $this->hasAccess;
    }

    // does the necessary initialization and checking if has access to post
    public abstract function initialise();

    public function toString(){}

    public abstract function postMessage($message, $serverData);

}
?>