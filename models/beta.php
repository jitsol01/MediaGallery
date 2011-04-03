<?php
class Beta extends AppModel {
	var $name = 'Beta';

	var $validate = array(
		'code' => array('rule' => array('alphaNumeric')),
		'id' => array('rule' => array('decimal', 0))
		'password' => array('rule' => array('alphaNumeric')),
		'ip' => array('rule' => array('ip'))
		);
}
?>
