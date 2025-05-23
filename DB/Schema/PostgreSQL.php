<?php

/**
 * Generator of the schemas corresponding to a given model.
 *
 * This class is for PostgreSQL, you can create a class on the same
 * model for another database engine.
 */
class Gatuf_DB_Schema_PostgreSQL {

	/**
	 * Mapping of the fields.
	 */
	public $mappings = array(
		'varchar' => 'character varying(%s)',
		'sequence' => 'serial',
		'boolean' => 'boolean',
		'date' => 'date',
		'datetime' => 'timestamp',
		'file' => 'character varying',
		'manytomany' => null,
		'foreignkey' => 'integer',
		'text' => 'text',
		'html' => 'text',
		'time' => 'time',
		'integer' => 'integer',
		'email' => 'character varying',
		'password' => 'character varying',
		'float' => 'real',
		'blob' => 'bytea',
		'char' => 'char(%s)',
		'time' => 'time',
		'longblob' => 'oid',
	);

	public $defaults = array(
		'varchar' => "''",
		'sequence' => null,
		'boolean' => 'FALSE',
		'date' => "'0001-01-01'",
		'datetime' => "'0001-01-01 00:00:00'",
		'file' => "''",
		'manytomany' => null,
		'foreignkey' => 0,
		'text' => "''",
		'html' => "''",
		'time' => "'00:00:00'",
		'integer' => 0,
		'email' => "''",
		'password' => "''",
		'float' => 0.0,
		'blob' => "''",
		'char' => "''",
		'time' => "'00:00:00'",
		'longblob' => null,
	);

	private $con = null;

	public function __construct($con) {
		$this->con = $con;
	}

	/**
	 * Get the SQL to generate the tables of the given model.
	 *
	 * @param
	 *			Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	function getSqlCreate($model) {
		$tables = array();
		$cols = $model->_a['cols'];
		$manytomany = array();
		$query = 'CREATE TABLE ' . $this->con->pfx . $model->_a['table'] . ' (';
		$sql_col = array();
		foreach ($cols as $col => $val) {
			$field = new $val['type']();
			if ($field->type != 'manytomany') {
				$fk = false;
				$sql = $this->con->qn($col) . ' ';
				if ($field->type == 'foreignkey') {
					$submodel = new $val['model']();
					$subfield = new $submodel->_a['cols'][$submodel->primary_key]['type']();
					if ($subfield->type != 'sequence') {
						$fk = true;
						$field = $subfield;
						$val = array_merge ($submodel->_a['cols'][$submodel->primary_key], $val);
					}
				}
				$_tmp = $this->process_mapping($field->type, $val);
				$sql .= $_tmp;
				if ($field->type != 'sequence' && empty($val['is_null'])) {
					$sql .= ' NOT NULL';
				}
				if (isset($val['default']) && !$fk) {
					$sql .= ' default ';
					$sql .= $model->_toDb($val['default'], $col);
				} elseif ($field->type != 'sequence' && !$fk && isset($this->defaults[$field->type])) {
					$sql .= ' default ' . $this->defaults[$field->type];
				}
				$sql_col[] = $sql;
			} else {
				$manytomany[] = $col;
			}
		}
		$sql_col[] = 'CONSTRAINT ' . $this->con->pfx . $model->_a['table'] . '_pkey PRIMARY KEY ('.$model->primary_key.')';
		$query = $query . "\n" . implode(",\n", $sql_col) . "\n" . ');';
		$tables[$this->con->pfx . $model->_a['table']] = $query;
		// Now for the many to many
		// FIXME add index on the second column
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$table = Gatuf_ModelUtils::getAssocTable($model, $omodel);
			
			$type_a = new $model->_a['cols'][$model->primary_key]['type']();
			$mapping_a = ($type_a->type == 'sequence') ? $this->mappings['foreignkey'] : $this->process_mapping($type_a->type, $model->_a['cols'][$model->primary_key]);
			$ra = Gatuf_ModelUtils::getAssocField($model);
			
			$type_b = new $omodel->_a['cols'][$omodel->primary_key]['type']();
			$mapping_b = ($type_b->type == 'sequence') ? $this->mappings['foreignkey'] : $this->process_mapping($type_b->type, $omodel->_a['cols'][$omodel->primary_key]);
			$rb = Gatuf_ModelUtils::getAssocField($omodel);

			$sql = 'CREATE TABLE ' . $table . ' (';
			$sql .= "\n" . $ra . ' ' . $mapping_a.',';
			$sql .= "\n" . $rb . ' ' . $mapping_b.',';
			$sql .= "\n" . 'CONSTRAINT ' . $this->getShortenedIdentifierName($this->con->pfx . $table . '_pkey') . ' PRIMARY KEY (' . $ra . ', ' . $rb . ')';
			$sql .= "\n" . ');';
			$tables[$this->con->pfx . $table] = $sql;
		}
		return $tables;
	}

	/**
	 * Get the SQL to generate the indexes of the given model.
	 *
	 * @param
	 *			Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	function getSqlIndexes($model) {
		$index = array();
		foreach ($model->_a['idx'] as $idx => $val) {
			if (!isset($val['col'])) {
				$val['col'] = $idx;
			}
			if ($val['type'] == 'unique') {
				$unique = 'UNIQUE ';
			} else {
				$unique = '';
			}

			$index[$this->con->pfx . $model->_a['table'] . '_' . $idx] = sprintf('CREATE ' . $unique . 'INDEX %s ON %s (%s);', $this->con->pfx . $model->_a['table'] . '_' . $idx, $this->con->pfx . $model->_a['table'], Gatuf_DB_Schema::quoteColumn($val['col'], $this->con));
		}
		foreach ($model->_a['cols'] as $col => $val) {
			$field = new $val['type']();
			if ($field->type == 'foreignkey') {
				$index[$this->con->pfx.$model->_a['table'].'_'.$col.'_foreignkey'] =
					sprintf(
						'CREATE INDEX %s ON %s (%s);',
						$this->con->pfx.$model->_a['table'].'_'.$col.'_fk_idx',
						$this->con->pfx.$model->_a['table'],
						Gatuf_DB_Schema::quoteColumn($col, $this->con)
					);
			}
			if (isset($val['unique']) and $val['unique'] == true) {
				$index[$this->con->pfx . $model->_a['table'] . '_' . $col . '_unique'] = sprintf('CREATE UNIQUE INDEX %s ON %s (%s);', $this->con->pfx . $model->_a['table'] . '_' . $col . '_unique_idx', $this->con->pfx . $model->_a['table'], Gatuf_DB_Schema::quoteColumn($col, $this->con));
			}
		}
		return $index;
	}
	
	public function process_mapping($type, $val) {
		$_tmp = $this->mappings[$type];
		if ($type == 'varchar') {
			if (isset($val['size'])) {
				$_tmp = sprintf($this->mappings['varchar'], $val['size']);
			} else {
				$_tmp = sprintf($this->mappings['varchar'], '150');
			}
		}
		/*if ($type == 'float') {
			if (!isset($val['max_digits'])) {
				$val['max_digits'] = 32;
			}
			if (!isset($val['decimal_places'])) {
				$val['decimal_places'] = 8;
			}
			$_tmp = sprintf($this->mappings['float'], $val['max_digits'], $val['decimal_places']);
		}*/
		if ($type == 'char') {
			if (isset($val['size'])) {
				$_tmp = sprintf($this->mappings['char'], $val['size']);
			} else {
				$_tmp = sprintf($this->mappings['char'], '10');
			}
		}
		return $_tmp;
	}
	
	/**
	 * All identifiers in Postgres must not exceed 64 characters in length.
	 *
	 * @param
	 *			string
	 * @return string
	 */
	function getShortenedIdentifierName($name)
	{
		if (strlen($name) <= 64) {
			return $name;
		}
		return substr($name, 0, 55) . '_' . substr(md5($name), 0, 8);
	}

	/**
	 * Get the SQL to create the constraints for the given model
	 *
	 * @param Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	function getSqlCreateConstraints($model) {
		$table = $this->con->pfx . $model->_a['table'];
		$constraints = array();
		$alter_tbl = 'ALTER TABLE ' . $table;
		$cols = $model->_a['cols'];
		$manytomany = array();

		foreach ($cols as $col => $val) {
			$field = new $val['type']();
			// remember these for later
			if ($field->type == 'manytomany') {
				$manytomany[] = $col;
			}
			if ($field->type == 'foreignkey') {
				// Add the foreignkey constraints
				$referto = new $val['model']();
				$constraints[] = $alter_tbl . ' ADD CONSTRAINT ' . $this->getShortenedIdentifierName($table . '_' . $col . '_fkey') . '
					FOREIGN KEY (' . $this->con->qn($col) . ')
					REFERENCES ' . $this->con->pfx . $referto->_a['table'] . ' ('.$referto->primary_key.') MATCH SIMPLE
					ON UPDATE CASCADE ON DELETE CASCADE';
			}
		}

		// Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$table = Gatuf_ModelUtils::getAssocTable($model, $omodel);
			$alter_tbl = 'ALTER TABLE ' . $table;
			$constraints[] = $alter_tbl . ' ADD CONSTRAINT ' . $this->getShortenedIdentifierName($table . '_fkey1') . '
				FOREIGN KEY (' . strtolower($model->_a['model']) . '_'.$model->primary_key.')
				REFERENCES ' . $this->con->pfx . $model->_a['table'] . ' ('.$model->primary_key.') MATCH SIMPLE
				ON UPDATE CASCADE ON DELETE CASCADE';
			$constraints[] = $alter_tbl . ' ADD CONSTRAINT ' . $this->getShortenedIdentifierName($table . '_fkey2') . '
				FOREIGN KEY (' . strtolower($omodel->_a['model']) . '_'.$omodel->primary_key.')
				REFERENCES ' . $this->con->pfx . $omodel->_a['table'] . ' ('.$omodel->primary_key.') MATCH SIMPLE
				ON UPDATE CASCADE ON DELETE CASCADE';
		}
		return $constraints;
	}

	/**
	 * Get the SQL to drop the tables corresponding to the model.
	 *
	 * @param Object Model
	 * @return string SQL string ready to execute.
	 */
	function getSqlDelete($model) {
		$cols = $model->_a['cols'];
		$manytomany = array();
		$sql = array();
		$sql[] = 'DROP TABLE IF EXISTS ' . $this->con->pfx . $model->_a['table'] . ' CASCADE';
		foreach ($cols as $col => $val) {
			$field = new $val['type']();
			if ($field->type == 'manytomany') {
				$manytomany[] = $col;
			}
		}

		// Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$table = Gatuf_ModelUtils::getAssocTable($model, $omodel);
			$sql[] = 'DROP TABLE IF EXISTS ' . $table . ' CASCADE';
		}
		return $sql;
	}

	/**
	 * Get the SQL to drop the constraints for the given model
	 *
	 * @param Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	function getSqlDeleteConstraints($model) {
		$table = $this->con->pfx . $model->_a['table'];
		$constraints = array();
		$alter_tbl = 'ALTER TABLE ' . $table;
		$cols = $model->_a['cols'];
		$manytomany = array();

		foreach ($cols as $col => $val) {
			$field = new $val['type']();
			// remember these for later
			if ($field->type == 'manytomany') {
				$manytomany[] = $col;
			}
			if ($field->type == 'foreignkey') {
				// Add the foreignkey constraints
				$referto = new $val['model']();
				$constraints[] = $alter_tbl . ' DROP CONSTRAINT ' . $this->getShortenedIdentifierName($table . '_' . $col . '_fkey');
			}
		}

		// Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$table = Gatuf_ModelUtils::getAssocTable($model, $omodel);
			$alter_tbl = 'ALTER TABLE ' . $table;
			$constraints[] = $alter_tbl . ' DROP CONSTRAINT ' . $this->getShortenedIdentifierName($table . '_fkey1');
			$constraints[] = $alter_tbl . ' DROP CONSTRAINT ' . $this->getShortenedIdentifierName($table . '_fkey2');
		}
		return $constraints;
	}
}

