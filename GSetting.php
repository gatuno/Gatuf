<?php

class Gatuf_GSetting extends Gatuf_Model {
	public $_model = __CLASS__;
	public $datacache = null;
	public $f = null;
	protected $_application = null;
	
	function init () {
		$this->_a['table'] = 'gsettings';
		$this->_a['model'] = __CLASS__;
		
		$this->primary_key = 'id';
		
		$this->_a['cols'] = array (
			'id' =>
			array (
			       'type' => 'Gatuf_DB_Field_Sequence',
			       'blank' => true,
			),
			'application' =>
			array (
			       'type' => 'Gatuf_DB_Field_Varchar',
			       'size' => 150,
			       'blank' => false,
			),
			'vkey' =>
			array (
			       'type' => 'Gatuf_DB_Field_Varchar',
			       'size' => 50,
			       'blank' => false,
			),
			'value' =>
			array (
			       'type' => 'Gatuf_DB_Field_Text',
			       'blank' => false,
			),
		);
		
		$this->_a['idx'] = array (
			'project_vkey_idx' =>
			array (
			       'col' => 'application, vkey',
			       'type' => 'unique',
			),
			'application_idx' =>
			array (
			       'col' => 'application',
			       'type' => 'index',
			),
		);
		
		/* FIXME: Ver si esto se va a usar o no
		$this->f = new IDF_Config_DataProxy () */
	}
	
	function setApp ($application) {
		$this->datacache = null;
		$this->_application = $application;
	}
	
	function initCache () {
		$this->datacache = new ArrayObject ();
		$sql = new Gatuf_SQL ('application=%s', $this->_application);
		foreach ($this->getList (array ('filter' => $sql->gen ())) as $val) {
			$this->datacache[$val->vkey] = $val->value;
		}
	}
	
	function setVal ($key, $value) {
		if (!is_null ($this->getVal ($key, null))
		    and $value == $this->getVal ($key)) {
			return;
		}
		$this->delVal ($key, false);
		$conf = new Gatuf_GSetting ();
		$conf->application = $this->_application;
		$conf->vkey = $key;
		$conf->value = $value;
		$conf->create ();
		$this->initCache ();
	}
	
	function getVal ($key, $default = '') {
		if ($this->datacache === null) {
			$this->initCache ();
		}
		return (isset ($this->datacache[$key])) ? $this->datacache[$key] : $default;
	}
	
	function delVal ($key, $initcache = true) {
		$gconf = new Gatuf_GSetting ();
		$sql = new Gatuf_SQL ('vkey=%s AND application=%s', array ($key, $this->_application));
		foreach ($gconf->getList (array ('filter' => $sql->gen ())) as $c) {
			$c->delete ();
		}
		if ($initcache) {
			$this->initCache ();
		}
	}
}
