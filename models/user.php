<?php
class User extends AppModel {
	var $name = 'User';
	var $displayField = 'username'; // show username instead of id in the scaffolding
	
	var $validate = array(
		'username' => array('rule' => array('alphaNumeric')),
		'email' => array('rule' => array('email')),
		'password' => array('rule' => array('alphaNumeric'))
		);
	function beforeSave()
	{
		// username and email should be unique
		return $this->checkUnique(array('username','email'));
	}
}
?>
