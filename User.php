<?php

class Gatuf_User extends Gatuf_Model {
	public $_model = __CLASS__;
	public $session_key = '_GATUF_Gatuf_User_auth';
	
	public $_cache_perms = null;
	
	public function init() {
		$langs = Gatuf::config('languages', array('en'));
		$this->_a['verbose'] = 'user';
		$this->_a['table'] = 'users';
		$this->_a['model'] = __CLASS__;
		$this->primary_key = 'id';
		$this->_a['cols'] = array(
			'id' =>
			array(
				'type' => 'Gatuf_DB_Field_Sequence',
				'blank' => true,
			),
			'login' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'unique' => true,
				'size' => 50,
			),
			'first_name' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 100,
			),
			'last_name' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 100,
			),
			'email' =>
			array(
				'type' => 'Gatuf_DB_Field_Email',
				'blank' => false,
			),
			'password' =>
			array(
				'type' => 'Gatuf_DB_Field_Password',
				'blank' => false,
				'size' => 150,
			),
			'groups' =>
			array(
				'type' => 'Gatuf_DB_Field_Manytomany',
				'blank' => true,
				'model' => Gatuf::config('gatuf_custom_group', 'Gatuf_Group'),
				'relate_name' => 'users',
			),
			'permissions' =>
			array(
				'type' => 'Gatuf_DB_Field_Manytomany',
				'blank' => true,
				'model' => 'Gatuf_Permission',
			),
			'administrator' =>
			array(
				'type' => 'Gatuf_DB_Field_Boolean',
				'default' => false,
				'blank' => true,
			),
			'staff' =>
			array(
				'type' => 'Gatuf_DB_Field_Boolean',
				'default' => false,
				'blank' => true,
			),
			'active' =>
			array(
				'type' => 'Gatuf_DB_Field_Boolean',
				'default' => true,
				'blank' => true,
			),
			'language' =>
			array(
				'type' => 'Gatuf_DB_Field_Char',
				'blank' => true,
				'default' => $langs[0],
				'size' => 5,
				/*'verbose' => __('language'),
				   'help_text' => __('Prefered language of the user for the interface. Use the 2 or 5 letter code like "fr", "en", "fr_QC" or "en_US".')*/
			),
			'last_login' =>
			array(
				'type' => 'Gatuf_DB_Field_Datetime',
				'blank' => true,
				'editable' => false,
				'is_null' => true,
				'default' => null,
			),
			'date_joined' =>
			array(
				'type' => 'Gatuf_DB_Field_Datetime',
				'blank' => true,
				'editable' => false,
				'is_null' => true,
				'default' => null,
			),
		);
		$this->_a['idx'] = array(
			'login_idx' =>
			array(
				'col' => 'login',
				'type' => 'unique',
			),
		);
		$this->_a['views'] = array();
		if (Gatuf::config('gatuf_custom_user', false)) {
			$this->extended_init();
		}
	}
	
	public function extended_init() {
		return;
	}
	
	public function __toString() {
		$repr = $this->last_name;
		if (strlen($this->first_name) > 0) {
			$repr = $this->first_name.' '.$repr;
		}
		return $repr;
	}
	
	public function preDelete() {
		$params = array('user' => $this);
		
		Gatuf_Signal::send('Gatuf_User::preDelete', 'Gatuf_User', $params);
	}
	
	public function setPassword($password) {
		$salt = Gatuf_Utils::getRandomString(5);
		$this->password = 'sha1:'.$salt.':'.sha1($salt.$password);
		return true;
	}
	
	public function checkPassword($password) {
		if ($this->password == '') {
			return false;
		}
		list($algo, $salt, $hash) = explode(':', $this->password);
		if ($hash == $algo($salt.$password)) {
			return true;
		} else {
			return false;
		}
	}
	
	public function checkCreditentials($login, $password) {
		$where = 'login = '.$this->_toDb($login, 'login');
		$users = $this->getList(array('filter' => $where));
		
		if ($users === false or count($users) !== 1) {
			return false;
		}
		
		if ($users[0]->active and $users[0]->checkPassword($password)) {
			return $users[0];
		}
		return false;
	}
	
	public function preSave($create = false) {
		if ($create) {
			//$this->last_login = gmdate('Y-m-d H:i:s');
			$this->date_joined = gmdate('Y-m-d H:i:s');
		}
	}
	
	public function isAnonymous() {
		return (0 === (int) $this->id);
	}
	
	public function getAllPermissions($force=false) {
		if ($force == false and !is_null($this->_cache_perms)) {
			return $this->_cache_perms;
		}
		
		$this->_cache_perms = array();
		$perms = (array) $this->get_permissions_list();
		$groups = $this->get_groups_list();
		$ids = array();
		foreach ($groups as $group) {
			$ids[] = $group->id;
		}
		
		if (count($ids) > 0) {
			$gperm = new Gatuf_Permission();
			$f_name = strtolower(Gatuf::config('gatuf_custom_group', 'Gatuf_Group')).'_id';
			$perms = array_merge($perms, (array) $gperm->getList(array('filter' => $f_name.' IN ('.join(', ', $ids).')', 'view' => 'join_group')));
		}
		foreach ($perms as $perm) {
			if (!in_array($perm->application.'.'.$perm->code_name, $this->_cache_perms)) {
				$this->_cache_perms[] = $perm->application.'.'.$perm->code_name;
			}
		}
		return $this->_cache_perms;
	}
	
	public function hasPerm($perm, $obj = null) {
		if ($this->isAnonymous()) {
			return false;
		}
		if (!$this->active) {
			return false;
		}
		if ($this->administrator) {
			return true;
		}
		$perms = $this->getAllPermissions();
		
		if (in_array($perm, $perms)) {
			return true;
		}
		
		return false;
	}
	
	public function hasAppPerms($app) {
		if ($this->administrator) {
			return true;
		}
		
		foreach ($this->getAllPermissions() as $perm) {
			if (0 === strpos($perm, $app.'.')) {
				return true;
			}
		}
		return false;
	}
	
	public function setMessage($type, $message) {
		if ($this->isAnonymous()) {
			return false;
		}
		
		$m = new Gatuf_Message();
		$m->message = $message;
		$m->type = $type;
		$m->user = $this;
		
		return $m->create();
	}
	
	public function getAndDeleteMessages() {
		if ($this->isAnonymous()) {
			return false;
		}
		$messages = new ArrayObject();
		$ms = $this->get_gatuf_message_list();
		foreach ($ms as $m) {
			$messages[] = array('message' => $m->message, 'type' => $m->type, 'id' => $m->id);
			$m->delete();
		}
		
		return $messages;
	}
	
	/**
	 * Get profile.
	 *
	 * Retrieve the profile of the current user. If not profile in the
	 * database a Pluf_Exception_DoesNotExist exception is thrown,
	 * just catch it and create a profile.
	 *
	 * @return Pluf_Model User profile
	 */
	public function getProfile() {
		$pclass = Gatuf::config('user_profile_class', false);
		if (false == $pclass) {
			throw new Gatuf_Exception_SettingError('"user_profile_class" setting not defined.');
		}
		$db = $this->_getConnection();
		$sql = new Gatuf_SQL(sprintf('%s=%%s', $db->qn('user')), array($this->id));
		$users = Gatuf::factory($pclass)->getList(array('filter' => $sql->gen()));
		if ($users->count() != 1) {
			throw new Gatuf_Exception_DoesNotExist(sprintf('No profiles available for user: %s', (string) $this));
		}
		return $users[0];
	}
}
