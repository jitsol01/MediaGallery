<?php

class UsersController extends AppController
{

//	var $scaffold;
	//var $component = array('Cookie');

	function index()
	{
		$this->set('logged_in',false);
		if($this->Session->check('id'))
		{
			$username = $this->Session->read('username');
			$id = $this->Session->read('id');
			$last_login = $this->Session->read('last_login');
			$username = $this->Session->read('username');
			$sess = $this->Session->read('session');
			$this->set('username',$username);
			$this->set('id',$id);
			$this->set('last_login',$last_login);
			$this->set('session_id',$sess);
			$this->set('logged_in',true);
		}
	}
	function settings()
	{
	
	}
	function login()
	{
		$id = (int) $this->readCookie('id');
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
        		$this->flash('User Account Cookie Valid','/');
        	}
        }
        
		if($this->data)
		{
			$results = $this->User->findByUsername(strtolower($this->data['User']['username']));

			if($results && $results['User']['password'] == md5(strtolower($this->data['User']['username']).$this->data['User']['password']))
			{
				
				// turn off checking
				//unset($this->User->validate['email']);
		
				// save user in database
				$results['User']['last_login'] = date("Y-m-d H:i:s");
				$results['User']['ip'] = $_SERVER['REMOTE_ADDR'];
				$results['User']['session'] = session_id();
				
				if($this->User->save($results))
				{
					// write session
					$this->Session->write('last_login', $results['User']['last_login']);
					$this->Session->write('id', $results['User']['id']);
					$this->Session->write('session', session_id());
					$this->Session->write('username', $results['User']['username']);
					// write cookie
					$this->writeCookie('session', session_id());
					$this->writeCookie('id', $results['User']['id']);
					
					
					$this->flash('success, logged in','/users/index');
				}
				else {
					// something funny happened
					// usually we failed validation/uniquecheck
					$this->flash('validation failed','/users/login');
				}
			}
			else
			{
				$this->flash('error logging in, password','/users/login');
			}
		}
	}
	function logout()
	{
		$this->Session->delete('id');
		$this->Session->delete('session');
		$this->destroyCookies();
		$this->redirect('/users/login');
		exit;
	}
	function register()
	{
		$this->pageTitle = 'New User Registration';
		if(!empty($this->data))
		{
			if(($this->data['User']['password'] == "") ||($this->data['User']['email'] == "") ||($this->data['User']['username'] == ""))
			{
				$this->flash('Error: email, username or password is blank.','/users/register');
			}
			
			// beta code
			// validate
			if (ereg('[^A-Za-z0-9]', $this->data['betacode'])) { $this->flash('Error: Beta code has invalid characters.','/users/register'); }
			// find the code
			$code = $this->User->query("SELECT * FROM `beta` WHERE `code` = '".$this->data['betacode']."' LIMIT 1");
			/* Check for invalid code or inuse code */
			if(!$code || ($code[0]['beta']['user_id'] != 0))
			{
				$this->flash('Error: Invalid/used beta code','/users/register'); // invalid
			}
			// end beta code
			
			// gotta have a lowercase login
			$this->data['User']['username'] = strtolower($this->data['User']['username']);
			// password = md5('usernamepassword');
			$this->data['User']['password'] = md5($this->data['User']['username'].$this->data['User']['password']);
			
			
			//$this->User->data = $this->data; // 1.2 validates()

			if($this->User->save($this->data))
			{
				// beta code
				$this->User->query("UPDATE `beta` SET `user_id` = ".$this->User->getLastInsertId()." WHERE `beta`.`id` = ".$code[0]['beta']['id']." LIMIT 1;");
				// end beta code
				$this->flash('Your account has been created. Please login.','/users/login');
			} 
			else {
				$this->flash('Invalid/Duplicate registration information.','/users/register');
					
			}
			
		}
	}
	
	function viewall()
	{
		// disabled for release
		exit;
		$this->pageTitle = 'View All Users';
		$this->set('allusers',$this->User->findAll(null,array('id','username','created','password'),'id DESC'));
	}

}

?>
