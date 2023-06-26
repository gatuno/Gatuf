<?php

class Gatuf_Permission extends Gatuf_Model {
	public $_model = 'Gatuf_Permission';
	
	public function init() {
		$this->_a['table'] = 'permissions';
		$this->_a['model'] = 'Gatuf_Permission';
		$this->primary_key = 'id';
		
		$this->_a['cols'] = array(
			'id' =>
			array(
				'type' => 'Gatuf_DB_Field_Sequence',
				'blank' => true,
			),
			'name' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 50,
			),
			'code_name' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 100,
			),
			'description' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'blank' => false,
				'size' => 250,
			),
			'application' =>
			array(
				'type' => 'Gatuf_DB_Field_Varchar',
				'size' => 150,
				'blank' => false,
			),
		);
		
		$this->_a['idx'] = array(
			'code_name_idx' =>
			array(
				'type' => 'normal',
				'col' => 'code_name',
			),
			'application_idx' =>
			array(
				'type' => 'normal',
				'col' => 'application',
			),
		);
		$omodel = Gatuf::factory (Gatuf::config('gatuf_custom_group', 'Gatuf_Group'));
		$t_asso = $this->getSqlTableNamepfx ().'.'.Gatuf_ModelUtils::getAssocTable($this, $omodel);
		
		$t_perm = $this->getSqlTable();
		$this->_a['views'] = array(
			'join_group' =>
			array(
				'join' => 'LEFT JOIN '.$t_asso
							 .' ON '.$t_perm.'.id=gatuf_permission_id',
			),
		);
	}
	
	public function __toString() {
		return $this->name.' ('.$this->application.'.'.$this->code_name.')';
	}
	
	public static function getFromString($perm) {
		list($app, $code) = explode('.', trim($perm));
		$sql = new Gatuf_SQL('code_name=%s AND application=%s', array($code, $app));
		
		$perms = Gatuf::factory('Gatuf_Permission')->getList(array('filter' => $sql->gen()));
		
		if ($perms->count() != 1) {
			return false;
		}
		
		return $perms[0];
	}
}
