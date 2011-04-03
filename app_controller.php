<?php
class AppController extends Controller {
	var $helpers = array('Html', 'Javascript', 'Form','Ajax');
	var $uses = array('User');
	//var $component = array('Cookie','Session');
	
	function checkInput($text)
	{
		return ereg('[^A-Za-z0-9]', $text);
	}
	function readCookie($id)
	{
		//echo $id.' :'.$_COOKIE[$id];
		if(isset($_COOKIE[$id])) return $_COOKIE[$id];
		return false;
	}
	function writeCookie($id,$val)
	{
		$expire = 30 * 60 * 24 * 60 + time(); // 30 days
		setcookie($id, $val, $expire,'/'); 
	}
	function destroyCookies()
	{
		// bad form, fuck you
		setcookie('id','',1,'/');
		setcookie('session','',1,'/');
	}

	function checkSession()
	{
 	// If the session is set...
		if ($this->Session->check('id'))
        {
        	$results = $this->User->find('User.id = '.$this->Session->read('id'),array('id','session','username','ip'));
        	
        	if($results && $results['User']['session'] == $this->Session->read('session') && $results['User']['ip'] == $_SERVER['REMOTE_ADDR'])
        	{
        		//$this->Session->setFlash('User Account Session Valid');
        		return $results;
        	}
        	// if we get here our session is invalid
        	// delete invalid session information
        	$this->Session->delete('id');
			$this->Session->delete('session');
        	$this->flash('invalid session information','/users/login');
        	exit;
        }
        
        //session information doesnt exist, so we will check the cookie and make session info
        $id = $this->readCookie('id');
        $sess = $this->readCookie('session');
        if($id && $sess)
        {
			$results = $this->User->findById($id);
			if($results && $results['User']['session'] == $sess && $results['User']['ip'] == $_SERVER['REMOTE_ADDR'])
			{
        		// valid user in cookie, create session data
   	     		$this->Session->write('session',$sess);
        		$this->Session->write('id',$id);
        		$this->Session->write('last_login', $results['User']['last_login']);
        		
        		return $results;
        	}
        	else // user not found from cookie
        	{
        		$this->destroyCookies();
        	}
        }
		else
		{
			// cookie has incomplete info, kill it
			$this->destroyCookies();
		}
        $this->flash('invalid login information','/users/login');
        exit; // we exit to prevent things from happening ** IMPORTANT **
	}
	function flash($msg,$to){
		$this->Session->setFlash($msg);
		$this->redirect($to);
		exit;
	}
}
?>
