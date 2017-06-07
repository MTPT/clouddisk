<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\DB\Wrapper;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;

class TableWrapper {

	/** @var Table */
	protected $table;

	/**
	 * @param Table $table
	 */
	public function __construct(Table $table) {
		$this->table = $table;
	}

	/**
	 * Sets the Primary Key.
	 *
	 * @param array          $columns
	 * @param string|boolean $indexName
	 * @return self
	 * @throws SchemaException
	 */
	public function setPrimaryKey(array $columns, $indexName = false) {
		if ($indexName !== false && strlen($indexName) > 30) {
			throw new SchemaException('Database schema error: Name of primary index key ' . $indexName . ' on table ' . $this->table->getName() . ' is too long (' . strlen($indexName) . '), max. 30 characters allowed');
		}
		$this->table->setPrimaryKey($columns, $indexName);

		return $this;
	}

	/**
	 * @param array       $columnNames
	 * @param string|null $indexName
	 * @param array       $flags
	 * @param array       $options
	 * @return self
	 * @throws SchemaException
	 */
	public function addIndex(array $columnNames, $indexName = null, array $flags = [], array $options = []) {

		if ($indexName === null) {
			throw new SchemaException('Database schema error: Name of index must not be empty on ' . $this->table->getName());
		}

		if (strlen($indexName) > 30) {
			throw new SchemaException('Database schema error: Name of index key ' . $indexName . ' on table ' . $this->table->getName() . ' is too long (' . strlen($indexName) . '), max. 30 characters allowed');
		}

		$this->table->addIndex($columnNames, $indexName, $flags, $options);
		return $this;
	}

	/**
	 * @param array       $columnNames
	 * @param string|null $indexName
	 * @param array       $options
	 * @return self
	 * @throws SchemaException
	 */
	public function addUniqueIndex(array $columnNames, $indexName = null, array $options = []) {

		if ($indexName === null) {
			throw new SchemaException('Database schema error: Name of unique index must not be empty on ' . $this->table->getName());
		}

		if (strlen($indexName) > 30) {
			throw new SchemaException('Database schema error: Name of index key ' . $indexName . ' on table ' . $this->table->getName() . ' is too long (' . strlen($indexName) . '), max. 30 characters allowed');
		}

		$this->table->addUniqueIndex($columnNames, $indexName, $options);
		return $this;
	}

	/**
	 * Renames an index.
	 *
	 * @param string      $oldIndexName The name of the index to rename from.
	 * @param string|null $newIndexName The name of the index to rename to.
	 *                                  If null is given, the index name will be auto-generated.
	 *
	 * @return self This table instance.
	 *
	 * @throws SchemaException if no index exists for the given current name
	 *                         or if an index with the given new name already exists on this table.
	 */
	public function renameIndex($oldIndexName, $newIndexName = null) {

		if ($newIndexName === null) {
			throw new SchemaException('Database schema error: Name of index must not be empty on ' . $this->table->getName());
		}

		if (strlen($newIndexName) > 30) {
			throw new SchemaException('Database schema error: Name of index key ' . $newIndexName . ' on table ' . $this->table->getName() . ' is too long (' . strlen($newIndexName) . '), max. 30 characters allowed');
		}

		$this->table->renameIndex($oldIndexName, $newIndexName);
		return $this;
	}

	/**
	 * @param string $columnName
	 * @param string $typeName
	 * @param array  $options
	 * @return Column
	 * @throws SchemaException
	 */
	public function addColumn($columnName, $typeName, array $options = []) {
		if (strlen($columnName) > 30) {
			throw new SchemaException('Database schema error: Name of column key ' . $columnName . ' on table ' . $this->table->getName() . ' is too long (' . strlen($columnName) . '), max. 30 characters allowed');
		}

		return $this->table->addColumn($columnName, $typeName, $options);
	}

	/**
	 * Adds a foreign key constraint.
	 *
	 * Name is inferred from the local columns.
	 *
	 * @param Table|string $foreignTable Table schema instance or table name
	 * @param array        $localColumnNames
	 * @param array        $foreignColumnNames
	 * @param array        $options
	 * @param string|null  $constraintName
	 * @return self
	 * @throws SchemaException
	 */
	public function addForeignKeyConstraint($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = [], $constraintName = null) {

		if ($constraintName === null) {
			throw new SchemaException('Database schema error: Name of foreign key must not be empty on ' . $this->table->getName());
		}

		if (strlen($constraintName) > 30) {
			throw new SchemaException('Database schema error: Name of foreign key ' . $constraintName . ' on table ' . $this->table->getName() . ' is too long (' . strlen($constraintName) . '), max. 30 characters allowed');
		}

		$this->table->addForeignKeyConstraint($foreignTable, $localColumnNames, $foreignColumnNames, $options, $constraintName);
		return $this;
	}

	/**
	 * Adds a foreign key constraint.
	 *
	 * Name is to be generated by the database itself.
	 *
	 * @deprecated Use {@link addForeignKeyConstraint}
	 *
	 * @param Table|string $foreignTable Table schema instance or table name
	 * @param array        $localColumnNames
	 * @param array        $foreignColumnNames
	 * @param array        $options
	 * @return self
	 * @throws SchemaException
	 */
	public function addUnnamedForeignKeyConstraint($foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = []) {
		throw new SchemaException('Database schema error: Name of foreign key must not be empty on ' . $this->table->getName());
	}

	/**
	 * Adds a foreign key constraint with a given name.
	 *
	 * @deprecated Use {@link addForeignKeyConstraint}
	 *
	 * @param string       $name
	 * @param Table|string $foreignTable Table schema instance or table name
	 * @param array        $localColumnNames
	 * @param array        $foreignColumnNames
	 * @param array        $options
	 * @return self
	 * @throws SchemaException
	 */
	public function addNamedForeignKeyConstraint($name, $foreignTable, array $localColumnNames, array $foreignColumnNames, array $options = []) {

		if ($name === null) {
			throw new SchemaException('Database schema error: Name of foreign key must not be empty on ' . $this->table->getName());
		}

		if (strlen($name) > 30) {
			throw new SchemaException('Database schema error: Name of foreign key ' . $name . ' on table ' . $this->table->getName() . ' is too long (' . strlen($name) . '), max. 30 characters allowed');
		}

		$this->table->addNamedForeignKeyConstraint($name, $foreignTable, $localColumnNames, $foreignColumnNames, $options);
		return $this;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments) {
		if (in_array($name, [
			'renameColumn',
			'changeColumn',
			'dropColumn',
			'addUnnamedForeignKeyConstraint',
			'addOption',
		], true)) {
			call_user_func_array([$this->table, $name], $arguments);
			return $this;
		}
		return call_user_func_array([$this->table, $name], $arguments);
	}
}
