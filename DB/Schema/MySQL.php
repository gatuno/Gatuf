<?php
/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Plume Framework, a simple PHP Application Framework.
# Copyright (C) 2001-2007 Loic d'Anterroches and contributors.
#
# Plume Framework is free software; you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation; either version 2.1 of the License, or
# (at your option) any later version.
#
# Plume Framework is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

/**
 * Generator of the schemas corresponding to a given model.
 *
 * This class is for MySQL, you can create a class on the same
 * model for another database engine.
 */
class Gatuf_DB_Schema_MySQL {
	/**
	 * Mapping of the fields.
	 */
	public $mappings = array(
		'varchar' => 'varchar(%s)',
		'sequence' => 'mediumint(9) unsigned not null auto_increment',
		'boolean' => 'bool',
		'date' => 'date',
		'datetime' => 'datetime',
		'file' => 'varchar(150)',
		'manytomany' => null,
		'foreignkey' => 'mediumint(9) unsigned',
		'text' => 'longtext',
		'html' => 'longtext',
		'time' => 'time',
		'integer' => 'integer',
		'email' => 'varchar(150)',
		'password' => 'varchar(150)',
		'float' => 'numeric(%s, %s)',
		'blob' => 'blob',
		'char' => 'char(%s)',
		'time' => 'time',
	);

	public $defaults = array(
		'varchar' => "''",
		'sequence' => null,
		'boolean' => 1,
		'file' => "''",
		'manytomany' => null,
		'foreignkey' => 0,
		'html' => "''",
		'time' => 0,
		'integer' => 0,
		'email' => "''",
		'password' => "''",
		'float' => 0.0,
		'blob' => "''",
		'char' => "''",
		'time' => "'00:00:00'",
	);
	private $con = null;

	public function __construct($con) {
		$this->con = $con;
	}

	/**
	 * Get the SQL to generate the tables of the given model.
	 *
	 * @param Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	public function getSqlCreate($model) {
		$tables = array();
		$cols = $model->_a['cols'];
		$manytomany = array();
		$sql = 'CREATE TABLE '.$this->con->dbname.'.`'.$this->con->pfx.$model->_a['table'].'` (';
		foreach ($cols as $col => $val) {
			$field = new $val['type']();
			if ($field->type != 'manytomany') {
				$fk = false;
				$sql .= "\n".$this->con->qn($col).' ';
				if ($field->type == 'foreignkey') {
					$submodel = new $val['model']();
					$subfield = new $submodel->_a['cols'][$submodel->primary_key]['type']();
					if ($subfield->type != 'sequence') {
						$fk = true;
						$field = $subfield;
						$val = array_merge($submodel->_a['cols'][$submodel->primary_key], $val);
					}
				}
				$_tmp = $this->process_mapping($field->type, $val);
				$sql .= $_tmp;
				if ($field->type != 'sequence' && empty($val['is_null'])) {
					$sql .= ' NOT NULL';
				}
				if (array_key_exists('default', $val) && !$fk) {
					$sql .= ' default ';
					$sql .= $model->_toDb($val['default'], $col);
				} elseif ($field->type != 'sequence' && !$fk && isset($this->defaults[$field->type])) {
					$sql .= ' default '.$this->defaults[$field->type];
				}
				$sql .= ',';
			} else {
				$manytomany[] = $col;
			}
		}
		$sql .= "\n".'PRIMARY KEY (`'.$model->primary_key.'`))';
		$sql .= 'ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		$tables[$this->con->pfx.$model->_a['table']] = $sql;

		// Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$hay = array(strtolower($model->_a['model']), strtolower($omodel->_a['model']));
			sort($hay);
			$table = $hay[0].'_'.$hay[1].'_assoc';
			$sql = 'CREATE TABLE '.$this->con->dbname.'.`'.$this->con->pfx.$table.'` (';
			
			$type_a = new $model->_a['cols'][$model->primary_key]['type']();
			$mapping_a = ($type_a->type == 'sequence') ? $this->mappings['foreignkey'] : $this->process_mapping($type_a->type, $model->_a['cols'][$model->primary_key]);
			$sql .= "\n".'`'.strtolower($model->_a['model']).'_'.$model->primary_key.'` '.$mapping_a.',';
			
			$type_b = new $omodel->_a['cols'][$omodel->primary_key]['type']();
			$mapping_b = ($type_b->type == 'sequence') ? $this->mappings['foreignkey'] : $this->process_mapping($type_b->type, $omodel->_a['cols'][$omodel->primary_key]);
			$sql .= "\n".'`'.strtolower($omodel->_a['model']).'_'.$omodel->primary_key.'` '.$mapping_b.',';
			
			$sql .= "\n".'PRIMARY KEY ('.strtolower($model->_a['model']).'_'.$model->primary_key.', '.strtolower($omodel->_a['model']).'_'.$omodel->primary_key.')';
			$sql .= "\n".') ENGINE=InnoDB';
			$sql .=' DEFAULT CHARSET=utf8;';
			$tables[$this->con->pfx.$table] = $sql;
		}
		return $tables;
	}

	/**
	 * Get the SQL to generate the indexes of the given model.
	 *
	 * @param Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	public function getSqlIndexes($model) {
		$index = array();
		foreach ($model->_a['idx'] as $idx => $val) {
			if (!isset($val['col'])) {
				$val['col'] = $idx;
			}
			if ($val['type'] == 'unique') {
				$index[$this->con->pfx.$model->_a['table'].'_'.$idx] =
					sprintf(
						'CREATE UNIQUE INDEX `%s` ON %s.`%s` (%s);',
						$idx,
						$this->con->dbname,
						$this->con->pfx.$model->_a['table'],
						Gatuf_DB_Schema::quoteColumn($val['col'], $this->con)
					);
			} else {
				$index[$this->con->pfx.$model->_a['table'].'_'.$idx] =
					sprintf(
						'CREATE INDEX `%s` ON %s.`%s` (%s);',
						$idx,
						$this->con->dbname,
						$this->con->pfx.$model->_a['table'],
						Gatuf_DB_Schema::quoteColumn($val['col'], $this->con)
					);
			}
		}
		foreach ($model->_a['cols'] as $col => $val) {
			$field = new $val['type']();
			if ($field->type == 'foreignkey') {
				$index[$this->con->pfx.$model->_a['table'].'_'.$col.'_foreignkey'] =
					sprintf(
						'CREATE INDEX `%s` ON %s.`%s` (`%s`);',
						$col.'_foreignkey_idx',
						$this->con->dbname,
						$this->con->pfx.$model->_a['table'],
						$col
					);
			}
			if (isset($val['unique']) and $val['unique'] == true) {
				$index[$this->con->pfx.$model->_a['table'].'_'.$col.'_unique'] =
					sprintf(
						'CREATE UNIQUE INDEX `%s` ON %s.`%s` (%s);',
						$col.'_unique_idx',
						$this->con->dbname,
						$this->con->pfx.$model->_a['table'],
						Gatuf_DB_Schema::quoteColumn($col, $this->con)
					);
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
		if ($type == 'float') {
			if (!isset($val['max_digits'])) {
				$val['max_digits'] = 32;
			}
			if (!isset($val['decimal_places'])) {
				$val['decimal_places'] = 8;
			}
			$_tmp = sprintf($this->mappings['float'], $val['max_digits'], $val['decimal_places']);
		}
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
	 * Workaround for <http://bugs.mysql.com/bug.php?id=13942> which limits the
	 * length of foreign key identifiers to 64 characters.
	 *
	 * @param string
	 * @return string
	 */
	public function getShortenedFKeyName($name) {
		if (strlen($name) <= 64) {
			return $name;
		}
		return substr($name, 0, 55).'_'.substr(md5($name), 0, 8);
	}

	/**
	 * Get the SQL to create the constraints for the given model
	 *
	 * @param Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	public function getSqlCreateConstraints($model) {
		$table = $this->con->pfx.$model->_a['table'];
		$constraints = array();
		$alter_tbl = 'ALTER TABLE '.$this->con->dbname.'.'.$table;
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
				$constraints[] = $alter_tbl.' ADD CONSTRAINT '.$this->getShortenedFKeyName($table.'_'.$col.'_fkey').'
				    FOREIGN KEY ('.$this->con->qn($col).')
				    REFERENCES '.$referto->_con->dbname.'.'.$referto->_con->pfx.$referto->_a['table'].' ('.$referto->primary_key.')
				    ON DELETE CASCADE ON UPDATE CASCADE';
			}
		}

		// Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$hay = array(strtolower($model->_a['model']), strtolower($omodel->_a['model']));
			sort($hay);
			$table = $this->con->pfx.$hay[0].'_'.$hay[1].'_assoc';
			$alter_tbl = 'ALTER TABLE '.$this->con->dbname.'.'.$table;
			$constraints[] = $alter_tbl.' ADD CONSTRAINT '.$this->getShortenedFKeyName($table.'_fkey1').'
			    FOREIGN KEY ('.strtolower($model->_a['model']).'_'.$model->primary_key.')
			    REFERENCES '.$model->_con->dbname.'.'.$model->_con->pfx.$model->_a['table'].' ('.$model->primary_key.')
			    ON DELETE CASCADE ON UPDATE CASCADE';
			$constraints[] = $alter_tbl.' ADD CONSTRAINT '.$this->getShortenedFKeyName($table.'_fkey2').'
			    FOREIGN KEY ('.strtolower($omodel->_a['model']).'_'.$omodel->primary_key.')
			    REFERENCES '.$omodel->_con->dbname.'.'.$omodel->_con->pfx.$omodel->_a['table'].' ('.$omodel->primary_key.')
			    ON DELETE CASCADE ON UPDATE CASCADE';
		}
		return $constraints;
	}

	/**
	 * Get the SQL to drop the tables corresponding to the model.
	 *
	 * @param Object Model
	 * @return string SQL string ready to execute.
	 */
	public function getSqlDelete($model) {
		$cols = $model->_a['cols'];
		$manytomany = array();
		$sql = 'DROP TABLE IF EXISTS '.$this->con->dbname.'.`'.$this->con->pfx.$model->_a['table'].'`';

		foreach ($cols as $col => $val) {
			$field = new $val['type']();
			if ($field->type == 'manytomany') {
				$manytomany[] = $col;
			}
		}

		//Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$hay = array(strtolower($model->_a['model']), strtolower($omodel->_a['model']));
			sort($hay);
			$table = $hay[0].'_'.$hay[1].'_assoc';
			$sql .= ', `'.$this->con->pfx.$table.'`';
		}
		return array($sql);
	}

	/**
	 * Get the SQL to drop the constraints for the given model
	 *
	 * @param Object Model
	 * @return array Array of SQL strings ready to execute.
	 */
	public function getSqlDeleteConstraints($model) {
		$table = $this->con->pfx.$model->_a['table'];
		$constraints = array();
		$alter_tbl = 'ALTER TABLE '.$this->con->dbname.'.'.$table;
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
				$constraints[] = $alter_tbl.' DROP FOREIGN KEY '.$this->getShortenedFKeyName($table.'_'.$col.'_fkey');
			}
		}

		// Now for the many to many
		foreach ($manytomany as $many) {
			$omodel = new $cols[$many]['model']();
			$hay = array(strtolower($model->_a['model']), strtolower($omodel->_a['model']));
			sort($hay);
			$table = $this->con->pfx.$hay[0].'_'.$hay[1].'_assoc';
			$alter_tbl = 'ALTER TABLE '.$table;
			$constraints[] = $alter_tbl.' DROP FOREIGN KEY '.$this->getShortenedFKeyName($table.'_fkey1');
			$constraints[] = $alter_tbl.' DROP FOREIGN KEY '.$this->getShortenedFKeyName($table.'_fkey2');
		}
		return $constraints;
	}
}
