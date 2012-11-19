<?php

/**
 * Users
 * 
 * @author karsten
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class Model_User extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'user';
	
	public function save(array $data){
		$errors = array();
		if (! Zend_Validate::is($data['username'], 'NotEmpty')) {
			$errors[] = "Please provide your last name.";
		}
		// Password must be at least 6 characters
		$valid_pswd = new Zend_Validate_StringLength ( 6, 20 );
		if (! $valid_pswd->isValid ( $data ['password'] )) {
			$errors [] = "Your password must be 6-20 characters.";
		}
		
		if (count($errors) == 0) {
			$data = array (
				'username' => $data['username'],
				'password' => md5($data['password'])
			);
			return $this->insert($data);
		}else{
			return $errors;
		}
	}

}

