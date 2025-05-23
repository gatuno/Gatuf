<?php
/* Gatuf's Creepy Version of the model */

Gatuf::loadFunction('Gatuf_DB_getConnection');

class Gatuf_Model {
	public $_model = __CLASS__; //set it to your model name
	
	/** Database connection. */
	public $_con = null;
	
	public $default_order = '';
	public $primary_key = 'id';
	
	protected $_data = array();
	
	protected $_fk = array();
	
	protected $_m = array('list' => array(), // get_*_list methods
		'many' => array(), // many to many
		'get' => array(), // foreign keys
		'extra' => array(), // added by some fields
	);
	/** 
	 * Store the attributes of the model. To minimize pollution of the
	 * property space, all the attributes are stored in this array.
	 *
	 * Description of the keys:
	 * 'table': The table in which the model is stored.
	 * 'model': The name of the model.
	 * 'cols': The definition of the columns.
	 * 'idx': The definition of the indexes.
	 * 'views': The definition of the views.
	 * 'verbose': The verbose name of the model.
	 */
	public $_a = array('table' => 'model',
		'model' => 'Gatuf_Model',
		'cols' => array(),
		'idx' => array(),
		'views' => array(),
	);
	
	public function __construct($pk = null, $values =array()) {
		$this->_init();
		
		if (!is_null($pk)) {
			$this->get($pk);
		}
	}
	
	public function init() {
		// Define it yourself.
	}
	
	public function _init() {
		$this->_getConnection();
		/* TODO: Inicializar cache */
		$this->init();
		foreach ($this->_a['cols'] as $col => $val) {
			$field = new $val['type']('', $col);
			
			$col_lower = strtolower($col);
			
			$type = 'foreignkey';
			if ($type === $field->type) {
				$this->_m['get']['get_'.$col_lower] = array($val['model'], $col);
				/* TODO: Caché aquí también */
				$this->_fk[$col] = $type;
			}
			
			$type = 'manytomany';
			if ($type === $field->type) {
				$this->_m['list']['get_'.$col_lower.'_list'] = $val['model'];
				$this->_m['many'][$val['model']] = $type;
			}
			
			foreach ($field->methods as $method) {
				$this->_m['extra'][$method[0]] = array($col_lower, $method[1]);
			}
			
			if (array_key_exists('default', $val)) {
				$this->_data[$col] = $val['default'];
			} else {
				$this->_data[$col] = '';
			}
		}
		
		$this->_setupAutomaticListMethods('foreignkey');
		$this->_setupAutomaticListMethods('manytomany');
		
		/* TODO: Caché */
	}
	
	/**
	 * Retrieve key relationships of a given model.
	 *
	 * @param string $model
	 * @param string $type Relation type: 'foreignkey' or 'manytomany'.
	 * @return array Key relationships.
	 */
	public function getRelationKeysToModel($model, $type) {
		$keys = array();
		foreach ($this->_a['cols'] as $col => $val) {
			$field_type = '';
			if ($val['type'] == 'Gatuf_DB_Field_Manytomany') {
				$field_type = 'manytomany';
			}
			if ($val['type'] == 'Gatuf_DB_Field_Foreignkey') {
				$field_type = 'foreignkey';
			}
			if (isset($val['model']) && $model === $val['model'] && $type === $field_type) {
				$keys[$col] = $val;
			}
		}

		return $keys;
	}
	
	/**
	 * Get the foreign keys relating to a given model.
	 *
	 * @deprecated Use {@link self::getRelationKeysToModel()} instead.
	 * @param string Model
	 * @return array Foreign keys
	 */
	public function getForeignKeysToModel($model) {
		return $this->getRelationKeysToModel($model, 'foreignkey');
	}
	
	public function getData() {
		foreach ($this->_a['cols'] as $col=>$val) {
			$field = new $val['type']();
			if ($field->type == 'manytomany') {
				$this->_data[$col] = array();
				$method = 'get_'.strtolower($col).'_list';
				foreach ($this->$method() as $item) {
					$this->_data[$col][] = $item->id;
				}
			}
		}
		return $this->_data;
	}
	
	/**
	 * Set the association of a model to another in many to many.
	 *
	 * @param object Object to associate to the current object
	 */
	public function setAssoc($model) {
		if (!$this->delAssoc($model)) {
			return false;
		}
		// Calcular la base de datos que contiene la relación M-N
		if (isset($GLOBALS['_GATUF_models_related']['manytomany'][$model->_a['model']]) && in_array($this->_a['model'], $GLOBALS['_GATUF_models_related']['manytomany'][$model->_a['model']])) {
			// La relación la tiene el $this
			$table = Gatuf_ModelUtils::getAssocTable ($this, $model);
			$pfxdbname = $this->getSqlTableNamepfx ();
		} else {
			$table = Gatuf_ModelUtils::getAssocTable ($model, $this);
			$pfxdbname = $model->getSqlTableNamepfx ();
		}
		$pk = $model->primary_key;
		$req = 'INSERT INTO '.$pfxdbname.'.'.$table."\n";
		$req .= '('.$this->_con->qn(strtolower($this->_a['model']).'_'.$this->primary_key).', '
			.$this->_con->qn(strtolower($model->_a['model']).'_'.$model->primary_key).') VALUES '."\n";
		$req .= '('.$this->_toDb($this->_data[$this->primary_key], $this->primary_key).', ';
		$req .= $model->_toDb($model->$pk, $model->primary_key).')';
		$this->_con->execute($req);
		return true;
	}
	
	/**
	 * Set the association of a model to another in many to many.
	 *
	 * @param object Object to associate to the current object
	 */
	public function delAssoc($model) {
		//check if ok to make the association
		//current model has a many to many key with $model
		//$model has a many to many key with current model
		$pk = $model->primary_key;
		if (!isset($this->_m['many'][$model->_a['model']])
			or strlen($this->_data[$this->primary_key]) == 0
			or strlen($model->$pk) == 0) {
			return false;
		}
		// Calcular la base de datos que contiene la relación M-N
		if (isset($GLOBALS['_GATUF_models_related']['manytomany'][$model->_a['model']]) && in_array($this->_a['model'], $GLOBALS['_GATUF_models_related']['manytomany'][$model->_a['model']])) {
			// La relación la tiene el $this
			$table = Gatuf_ModelUtils::getAssocTable ($this, $model);
			$pfxdbname = $this->getSqlTableNamepfx ();
		} else {
			$table = Gatuf_ModelUtils::getAssocTable ($model, $this);
			$pfxdbname = $model->getSqlTableNamepfx ();
		}
		$req = 'DELETE FROM '.$pfxdbname.'.'.$table.' WHERE'."\n";
		$req .= $this->_con->qn(strtolower($this->_a['model']).'_'.$this->primary_key).' = '.$this->_toDb($this->_data[$this->primary_key], $this->primary_key);
		$req .= ' AND '.$this->_con->qn(strtolower($model->_a['model']).'_'.$model->primary_key).' = '.$model->_toDb($model->$pk, $model->primary_key);
		$this->_con->execute($req);
		return true;
	}
	
	/**
	 * Bulk association of models to the current one.
	 *
	 * @param string Model name
	 * @param array Ids of Model name
	 * @return bool Success
	 */
	public function batchAssoc($model_name, $ids) {
		$currents = $this->getRelated($model_name);
		foreach ($currents as $cur) {
			$this->delAssoc($cur);
		}
		foreach ($ids as $id) {
			$m = new $model_name($id);
			$key = $m->primary_key;
			if ($m->$key == $id) {
				$this->setAssoc($m);
			}
		}
		return true;
	}
	
	public function _getConnection() {
		static $con = null;
		if ($this->_con !== null) {
			return $this->_con;
		}
		if ($con !== null) {
			$this->_con = $con;
			return $this->_con;
		}
		$this->_con = &Gatuf::db($this);
		$con = $this->_con;
		return $this->_con;
	}
	
	public function getSqlTableNamepfx () {
		if (property_exists ($this->_con, 'search_path')) {
			return $this->_con->dbname.'.'.$this->_con->search_path;
		}
		
		return $this->_con->dbname;
	}
	
	public function getSqlTable() {
		if (property_exists ($this->_con, 'search_path')) {
			return $this->_con->dbname.'.'.$this->_con->search_path.'.'.$this->_con->pfx.$this->_a['table'];
		}
		return $this->_con->dbname.'.'.$this->_con->pfx.$this->_a['table'];
	}
	
	/**
	 * Overloading of the get method.
	 *
	 * @param string Property to get
	 */
	public function __get($prop) {
		return (array_key_exists($prop, $this->_data)) ?
			$this->_data[$prop] : $this->__call($prop, array());
	}
	
	/**
	 * Overloading of the set method.
	 *
	 * @param string Property to set
	 * @param mixed Value to set
	 */
	public function __set($prop, $val) {
		if (null !== $val and isset($this->_fk[$prop])) {
			$key = $val->primary_key;
			$this->_data[$prop] = $val->$key;
		//unset($this->_cache['get_'.$prop]);
		} else {
			$this->_data[$prop] = $val;
		}
	}
	
	/**
	 * Overloading of the method call.
	 *
	 * @param string Method
	 * @param array Arguments
	 */
	public function __call($method, $args) {
		// The foreign keys of the current object.
		if (isset($this->_m['get'][$method])) {
			/*if (isset($this->_cache[$method])) {
				return $this->_cache[$method];
			} else */{
				/*$this->_cache[$method] = Gatuf::factory($this->_m['get'][$method][0], $this->_data[$this->_m['get'][$method][1]]);
				if ($this->_cache[$method]->id == '') $this->_cache[$method] = null;
				return $this->_cache[$method];*/
				$ret = new $this->_m['get'][$method][0]();
				if (!$ret->get($this->_data[$this->_m['get'][$method][1]])) {
					$ret = null;
				}
				
				return $ret;
			}
		}
		// Many to many or foreign keys on the other objects.
		if (isset($this->_m['list'][$method])) {
			if (is_array($this->_m['list'][$method])) {
				$model = $this->_m['list'][$method][0];
			} else {
				$model = $this->_m['list'][$method];
			}
			$args = array_merge(array($model, $method), $args);
			return call_user_func_array(array($this, 'getRelated'), $args);
		}
		// Extra methods added by fields
		if (isset($this->_m['extra'][$method])) {
			$args = array_merge(array($this->_m['extra'][$method][0], $method, $this), $args);
			Gatuf::loadFunction($this->_m['extra'][$method][1]);
			return call_user_func_array($this->_m['extra'][$method][1], $args);
		}
		throw new Exception(sprintf('Method "%s" not available.', $method));
	}
	
	/**
	 * Get a given item.
	 *
	 * @param int Id of the item.
	 * @return mixed Item or false if not found.
	 */
	public function get($id) {
		$req = 'SELECT * FROM '.$this->getSqlTable().' WHERE '.$this->primary_key.'='.$this->_toDb($id, $this->primary_key);
		if (false === ($rs = $this->_con->select($req))) {
			throw new Exception($this->_con->getError());
		}
		if (count($rs) == 0) {
			return false;
		}
		foreach ($this->_a['cols'] as $col => $val) {
			$field = new $val['type']();
			if ($field->type != 'manytomany' && array_key_exists($col, $rs[0])) {
				$this->_data[$col] = $this->_fromDb($rs[0][$col], $col);
			}
		}
		$this->restore();
		return $this;
	}
	
	public function getOne($p=array()) {
		if (!is_array($p)) {
			$p = array('filter' => $p);
		}
		
		$items = $this->getList($p);
		if ($items->count() == 1) {
			return $items[0];
		}
		if ($items->count() == 0) {
			return null;
		}
		throw new Exception(__('Error: More than one matching item found.'));
	}
	
	public function getList($p=array()) {
		$default = array('view' => null,
			'filter' => null,
			'order' => null,
			'start' => null,
			'select' => null,
			'nb' => null,
			'count' => false);
		$p = array_merge($default, $p);
		if (!is_null($p['view']) && !isset($this->_a['views'][$p['view']])) {
			throw new Exception(sprintf(__('The view "%s" is not defined.'), $p['view']));
		}
		$query = array(
			'select' => $this->getSelect(),
			'from' => $this->getSqlTable(),
			'join' => '',
			'where' => '',
			'group' => '',
			'having' => '',
			'order' => $this->default_order,
			'limit' => '',
			'props' => array(),
		);
		
		if (!is_null($p['view'])) {
			$query = array_merge($query, $this->_a['views'][$p['view']]);
		}
		
		if (!is_null($p['select'])) {
			$query['select'] = $p['select'];
		}
		/* Activar los filtros where */
		if (!is_null($p['filter'])) {
			if (is_array($p['filter'])) {
				$p['filter'] = implode(' AND ', $p['filter']);
			}
			if (strlen($query['where']) > 0) {
				$query['where'] .= ' AND ';
			}
			$query['where'] .= ' ('.$p['filter'].') ';
		}
		
		/* Elegir el orden */
		if (!is_null($p['order'])) {
			if (is_array($p['order'])) {
				$p['order'] = implode(', ', $p['order']);
			}
			if (strlen($query['order']) > 0 and strlen($p['order']) > 0) {
				$p['order'] .= ', ';
			}
			$query['order'] = $p['order'].$query['order'];
		}
		/* El número de objetos a elegir */
		if (!is_null($p['start']) && is_null($p['nb'])) {
			$p['nb'] = 10000000;
		}
		/* El inicio */
		if (!is_null($p['start'])) {
			if ($p['start'] != 0) {
				$p['start'] = (int) $p['start'];
			}
			$p['nb'] = (int) $p['nb'];
			$query['limit'] = 'LIMIT '.$p['nb'].' OFFSET '.$p['start'];
		}
		if (!is_null($p['nb']) && is_null($p['start'])) {
			$p['nb'] = (int) $p['nb'];
			$query['limit'] = 'LIMIT '.$p['nb'];
		}
		/* Si la query es de conteo, cambiar el select */
		if ($p['count'] == true) {
			if (isset($query['select_count'])) {
				$query['select'] = $query['select_count'];
			} else {
				$query['select'] = 'COUNT(*) as nb_items';
			}
			$query['order'] = '';
			$query['limit'] = '';
		}
		
		/* Construir la query */
		$req = 'SELECT '.$query['select'].' FROM '.$query['from'].' '.$query['join'];
		if (strlen($query['where'])) {
			$req .= "\n".'WHERE '.$query['where'];
		}
		if (strlen($query['group'])) {
			$req .= "\n".'GROUP BY '.$query['group'];
		}
		if (strlen($query['having'])) {
			$req .= "\n".'HAVING '.$query['having'];
		}
		if (strlen($query['order'])) {
			$req .= "\n".'ORDER BY '.$query['order'];
		}
		if (strlen($query['limit'])) {
			$req .= "\n".$query['limit'];
		}
		
		if (false === ($rs=$this->_con->select($req))) {
			throw new Exception($this->_con->getError());
		}
		
		if (count($rs) == 0) {
			return new ArrayObject();
		}
		
		if ($p['count'] == true) {
			if (empty($rs) or count($rs) == 0) {
				return 0;
			} else {
				return (int) $rs[0]['nb_items'];
			}
		}
		
		$res = new ArrayObject();
		foreach ($rs as $row) {
			$this->_reset();
			foreach ($this->_a['cols'] as $col => $val) {
				if (isset($row[$col])) {
					$this->_data[$col] = $this->_fromDb($row[$col], $col);
				}
			}
			
			foreach ($query['props'] as $prop) {
				$this->_data[$prop] = (isset($row[$prop])) ? $row[$prop] : null;
			}
			
			$this->restore();
			$res[] = clone ($this);
		}
		
		return $res;
	}
	
	/**
	 * Get a list of related items.
	 *
	 * See the getList() method for usage of the view and filters.
	 *
	 * @param string Class of the related items
	 * @param string Method call in a many to many related
	 * @param array Parameters, see getList() for the definition of
	 *			  the keys
	 * @return array Array of items
	 */
	public function getRelated($model, $method=null, $p=array()) {
		$default = array('view' => null,
			'filter' => null,
			'order' => null,
			'start' => null,
			'nb' => null,
			'count' => false);
		$p = array_merge($default, $p);
		if ('' == $this->_data[$this->primary_key]) {
			return new ArrayObject();
		}
		$m = new $model();
		if (isset($this->_m['list'][$method])
			and is_array($this->_m['list'][$method])) {
			$foreignkey = $this->_m['list'][$method][1];
			if (strlen($foreignkey) == 0) {
				throw new Exception(sprintf(__('No matching foreign key found in model: %s for model %s'), $model, $this->_a['model']));
			}
			if (!is_null($p['filter'])) {
				if (is_array($p['filter'])) {
					$p['filter'] = implode(' AND ', $p['filter']);
				}
				$p['filter'] .= ' AND ';
			} else {
				$p['filter'] = '';
			}
			$p['filter'] .= $m->getSqlTable().'.'.$this->_con->qn($foreignkey).'='.$this->_toDb($this->_data[$this->primary_key], $this->primary_key);
		} else {
			// Many to many: We generate a special view that is making
			// the join
			// Calcular la base de datos que contiene la relación M-N
			if (isset($GLOBALS['_GATUF_models_related']['manytomany'][$m->_a['model']]) && in_array($this->_a['model'], $GLOBALS['_GATUF_models_related']['manytomany'][$m->_a['model']])) {
				// La relación la tiene el $this
				$table = Gatuf_ModelUtils::getAssocTable ($this, $m);
				$pfxdbname = $this->getSqlTableNamepfx ();
			} else {
				$table = Gatuf_ModelUtils::getAssocTable ($m, $this);
				$pfxdbname = $model->getSqlTableNamepfx ();
			}
			if (isset($m->_a['views'][$p['view']])) {
				$m->_a['views'][$p['view'].'__manytomany__'] = $m->_a['views'][$p['view']];
				if (!isset($m->_a['views'][$p['view'].'__manytomany__']['join'])) {
					$m->_a['views'][$p['view'].'__manytomany__']['join'] = '';
				}
				if (!isset($m->_a['views'][$p['view'].'__manytomany__']['where'])) {
					$m->_a['views'][$p['view'].'__manytomany__']['where'] = '';
				}
			} else {
				$m->_a['views']['__manytomany__'] = array('join' => '',
					'where' => '');
				$p['view'] = '';
			}
			$m->_a['views'][$p['view'].'__manytomany__']['join'] .=
				' LEFT JOIN '.$pfxdbname.'.'.$table.' ON '
				.$this->_con->qn(strtolower($m->_a['model']).'_'.$m->primary_key).' = '.(isset($m->_a['views'][$p['view'].'__manytomany__']['from']) ? $m->_a['views'][$p['view'].'__manytomany__']['from'] : $m->getSqlTable()).'.'.$m->primary_key;

			$m->_a['views'][$p['view'].'__manytomany__']['where'] = $this->_con->qn(strtolower($this->_a['model']).'_'.$this->primary_key).'='.$this->_con->esc($this->_data[$this->primary_key]);
			$p['view'] = $p['view'].'__manytomany__';
		}
		return $m->getList($p);
	}
	
	/**
	 * Generate the SQL select from the columns
	 */
	public function getSelect() {
		/*if (isset($this->_cache['getSelect'])) return $this->_cache['getSelect']; FIXME: Caché */
		$select = array();
		$table = $this->getSqlTable();
		foreach ($this->_a['cols'] as $col=>$val) {
			if ($val['type'] != 'Gatuf_DB_Field_Manytomany') {
				$select[] = $table.'.'.$this->_con->qn($col).' AS '.$this->_con->qn($col);
			}
		}
		/*$this->_cache['getSelect'] = implode(', ', $select);
		return $this->_cache['getSelect'];*/
		return implode(', ', $select);
	}
	
	/**
	 * Update the model into the database.
	 *
	 * If no where clause is provided, the index definition is used to
	 * find the sequence. These are used to limit the update
	 * to the current model.
	 *
	 * @param string Where clause to update specific items. ('')
	 * @return bool Success
	 */
	public function update($where='') {
		$this->preSave(false);
		$req = 'UPDATE '.$this->getSqlTable().' SET'."\n";
		$fields = array();
		$assoc = array();
		foreach ($this->_a['cols'] as $col=>$val) {
			$field = new $val['type']();
			if ($col == $this->primary_key) {
				continue;
			} elseif ($field->type == 'manytomany') {
				if (is_array($this->$col)) {
					$assoc[$val['model']] = $this->$col;
				}
				continue;
			}
			$fields[] = $this->_con->qn($col).' = '.$this->_toDb($this->$col, $col);
		}
		$req .= implode(','."\n", $fields);
		if (strlen($where) > 0) {
			$req .= ' WHERE '.$where;
		} else {
			$req .= ' WHERE '.$this->primary_key.' = '.$this->_toDb($this->_data[$this->primary_key], $this->primary_key);
		}
		$this->_con->execute($req);
		if (false === $this->get($this->_data[$this->primary_key])) {
			return false;
		}
		foreach ($assoc as $model=>$ids) {
			$this->batchAssoc($model, $ids);
		}
		$this->postSave(false);
		return true;
	}
	
	/**
	 * Create the model into the database.
	 *
	 * If raw insert is requested, the preSave/postSave methods are
	 * not called and the current id of the object is directly
	 * used. This is particularily used when doing backup/restore of
	 * data.
	 * 
	 * @param bool Raw insert (false)
	 * @return bool Success
	 */
	public function create($raw=false) {
		if (!$raw) {
			$this->preSave(true);
		}
		$req = 'INSERT INTO '.$this->getSqlTable()."\n";
		$icols = array();
		$ivals = array();
		$assoc = array();
		foreach ($this->_a['cols'] as $col=>$val) {
			$field = new $val['type']();
			if ($col == 'id' and !$raw) {
				continue;
			} elseif ($field->type == 'manytomany') {
				// If is a defined array, we need to associate.
				if (is_array($this->_data[$col])) {
					$assoc[$val['model']] = $this->_data[$col];
				}
				continue;
			}
			$icols[] = $this->_con->qn($col);
			$ivals[] = $this->_toDb($this->_data[$col], $col);
		}
		$req .= '('.implode(', ', $icols).') VALUES ';
		$req .= '('.implode(','."\n", $ivals).')';
		$this->_con->execute($req);
		if (!$raw && $this->primary_key == 'id') {
			if (false === ($id=$this->_con->getLastID())) {
				throw new Exception($this->_con->getError());
			}
			$this->_data['id'] = $id;
		}
		foreach ($assoc as $model=>$ids) {
			$this->batchAssoc($model, $ids);
		}
		if (!$raw) {
			$this->postSave(true);
		}
		return true;
	}
	
	/**
	 * Get models affected by delete.
	 *
	 * @return array Models deleted if deleting current model.
	 */
	public function getDeleteSideEffect() {
		$affected = array();
		foreach ($this->_m['list'] as $method=>$details) {
			if (is_array($details)) {
				// foreignkey
				$related = $this->$method();
				$affected = array_merge($affected, (array) $related);
				foreach ($related as $rel) {
					if ($details[0] == $this->_a['model']
						and $rel->$rel->primary_key == $this->_data[$this->primary_key]) {
						continue; // $rel == $this
					}
					$affected = array_merge($affected, (array) $rel->getDeleteSideEffect());
				}
			}
		}
		return Gatuf_Model_RemoveDuplicates($affected);
	}
	
	/**
	 * Delete the current model from the database.
	 *
	 * If another model link to the current model through a foreign
	 * key, find it and delete it. If this model is linked to other
	 * through a many to many, delete the association.
	 *
	 * FIXME: No real test of circular references. It can break.
	 */
	public function delete() {
		if (false === $this->get($this->_data[$this->primary_key])) {
			return false;
		}
		$this->preDelete();
		// Drop the row level permissions if we are using them
		// No usado
		/*if (Pluf::f('pluf_use_rowpermission', false)) {
			$_rpt = Pluf::factory('Pluf_RowPermission')->getSqlTable();
			$sql = new Pluf_SQL('model_class=%s AND model_id=%s',
								array($this->_a['model'], $this->_data['id']));
			$this->_con->execute('DELETE FROM '.$_rpt.' WHERE '.$sql->gen());
		}*/
		// Find the models linking to the current one through a foreign key.
		foreach ($this->_m['list'] as $method=>$details) {
			if (is_array($details)) {
				// foreignkey
				$related = $this->$method();
				foreach ($related as $rel) {
					if ($details[0] == $this->_a['model']
						and $rel->$rel->primary_key == $this->_data[$this->primary_key]) {
						continue; // $rel == $this
					}
					// We do not really control if it can be deleted
					// as we can find many times the same to delete.
					$rel->delete();
				}
			} else {
				// manytomany
				$related = $this->$method();
				foreach ($related as $rel) {
					$this->delAssoc($rel);
				}
			}
		}
		$req = 'DELETE FROM '.$this->getSqlTable().' WHERE '.$this->primary_key.' = '.$this->_toDb($this->_data[$this->primary_key], $this->primary_key);
		$this->_con->execute($req);
		$this->_reset();
		return true;
	}
	
	/**
	 * Reset the fields to default values.
	 */
	public function _reset() {
		foreach ($this->_a['cols'] as $col => $val) {
			if (isset($val['default'])) {
				$this->_data[$col] = $val['default'];
			} elseif (isset($val['is_null'])) {
				$this->_data[$col] = null;
			} else {
				$this->_data[$col] = '';
			}
		}
	}
	
	public function getCount($p=array()) {
		$p['count'] = true;
		$count = $this->getList($p);
		return (int) $count;
	}
	
	public function displayVal($field) {
		return $this->$field;
	}
	
	public function restore() {
	}
	
	public function preSave($create=false) {
	}
	
	public function postSave($create=false) {
	}
	
	public function preDelete() {
	}
	
	public function setFromFormData($cleaned_values) {
		foreach ($cleaned_values as $key=>$val) {
			$this->_data[$key] = $val;
		}
	}
	
	public function _toDb($val, $col) {
		$m = $this->_con->type_cast[$this->_a['cols'][$col]['type']][1];
		return $m($val, $this->_con);
	}
	
	public function _fromDb($val, $col) {
		$m = $this->_con->type_cast[$this->_a['cols'][$col]['type']][0];
		if ($m == 'Gatuf_DB_PostgreSQL_LOIDFromDb') return $m($val, $this->_con);
		return ($m == 'Gatuf_DB_IdentityFromDb') ? $val : $m($val);
	}
	
	protected function _setupAutomaticListMethods($type) {
		$current_model = $this->_a['model'];
		if (isset($GLOBALS['_GATUF_models_related'][$type][$current_model])) {
			$relations = $GLOBALS['_GATUF_models_related'][$type][$current_model];
			foreach ($relations as $related) {
				if ($related != $current_model) {
					$model = new $related();
				} else {
					$model = clone $this;
				}
				$fkeys = $model->getRelationKeysToModel($current_model, $type);
				foreach ($fkeys as $fkey => $val) {
					$mname = (isset($val['relate_name'])) ? $val['relate_name'] : $related;
					$mname = 'get_'.strtolower($mname).'_list';
					if ('foreignkey' === $type) {
						$this->_m['list'][$mname] = array($related, $fkey);
					} else {
						$this->_m['list'][$mname] = $related;
						$this->_m['many'][$related] = $type;
					}
				}
			}
		}
	}
}

function Gatuf_Model_InArray($model, $array) {
	if ($model->$model->primary_key == '') {
		return false;
	}
	
	foreach ($array as $modelin) {
		if ($modelin->_a['model'] == $model->_a['model']
			and $modelin->$modelin->primary_key == $model->$model->primary_key) {
			return true;
		}
	}
	return false;
}

function Gatuf_Model_RemoveDuplicates($array) {
	$res = array();
	foreach ($array as $model) {
		if (!Gatuf_Model_InArray($model, $res)) {
			$res[] = $model;
		}
	}
	return $res;
}
