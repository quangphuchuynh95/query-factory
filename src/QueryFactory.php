<?php

namespace Quangphuc\QueryFactory;

class QueryFactory {
    const NONE_ESCAPE_FIELD_NAMES = ['*'];

    protected $table;
    protected $pk;
    protected $unsafeDelete = FALSE;
    protected $unsafeUpdate = FALSE;

    /**
     * @param array $config
     */
    public function __construct(array $config = []) {
        if (!empty($config['table'])) {
            $this->table = $config['table'];
        }
        if (!empty($config['pk'])) {
            $this->pk = $config['pk'];
        }
        if (!empty($config['unsafeDelete'])) {
            $this->unsafeDelete = $config['unsafeDelete'];
        }
        if (!empty($config['unsafeUpdate'])) {
            $this->unsafeUpdate = $config['unsafeUpdate'];
        }
    }

    /**
     * @param string|array $fields
     * @param array $where
     */
    public function select($fields = '*', $where = []) {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        $fields = array_map(function ($field) {
            if (in_array($field, self::NONE_ESCAPE_FIELD_NAMES)) {
                return $field;
            }
            return QueryFactoryHelper::escapeIdentifier($field);
        }, $fields);
        $query = "SELECT " . implode(',', $fields)
            . " FROM " . $this->table;
        if ($where) {
            $query .= ' WHERE ' . QueryFactoryHelper::parseWhereClause($where);
        }
        return $query;
    }

    /**
     * @param              $pk
     * @param string|array $fields
     *
     * @return string
     */
    public function selectByPk($pk, $fields = '*') {
        return $this->select($fields, [
            $this->pk => $pk
        ]);
    }

    /**
     * @param array $where
     *
     * @throws QueryFactoryException
     */
    public function delete($where = []) {
        $query = "DELETE FROM {$this->table} WHERE";
        if ($where) {
            $query .= QueryFactoryHelper::parseWhereClause($where);
        } else if (!$this->unsafeDelete) {
            throw new QueryFactoryException('You are trying to remove data without where clause, please enable $unsafeDelete if you want');
        } else {
            $query .= 'TRUE';
        }
        return $query;
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function deleteByPk($id) {
        return "DELETE FROM {$this->table} WHERE " . QueryFactoryHelper::parseWhereClause([
            $this->pk => $id,
        ]);
    }

    /**
     * @param       $values
     * @param array $where
     * @param bool  $return
     *
     * @return string
     * @throws QueryFactoryException
     */
    public function update($values, array $where, $return = FALSE) {
        $query = "UPDATE {$this->table} SET ";
        $setClauses = [];
        foreach ($values as $key => $value) {
            $setClauses[] = QueryFactoryHelper::escapeIdentifier($key) . ' = ' . QueryFactoryHelper::escapeValue($value);
        }
        $query .= implode(', ', $setClauses);
        $query .= ' WHERE ';
        if ($where) {
            $query .= QueryFactoryHelper::parseWhereClause($where);
        } else if (!$this->unsafeDelete) {
            throw new QueryFactoryException('You are trying to update data without WHERE clause, please enable $unsafeUpdate if you want');
        } else {
            $query .= 'TRUE';
        }
        if ($return) {
            $query .= 'RETURNING *';
        }
        return $query;
    }

    /**
     * @param       $id
     * @param array $values
     *
     * @return string
     * @throws QueryFactoryException
     */
    public function updateOne($id, $values) {
        return $this->update($values, [
            $this->pk => $id,
        ]);
    }

    /**
     * @param       $values
     * @param bool  $return
     *
     * @return string
     */
    public function insert($values, $return = FALSE) {
        $query = "INSERT INTO {$this->table} ";
        $keys = [];
        if (QueryFactoryHelper::isAssoc($values)) {
            $keys = array_keys($values);
        } else {
            $keys = array_keys($values[0]);
        }
        $keys = array_map(function ($key) {
            return QueryFactoryHelper::escapeIdentifier($key);
        }, $keys);
        $query .= '(' . implode(',', $keys) . ') VALUES ';
        if (QueryFactoryHelper::isAssoc($values)) {
            $values = [$values];
        }
        $rows = [];
        foreach ($values as $rowValues) {
            $rowItems = array_map(function ($value) {
                return QueryFactoryHelper::escapeValue($value);
            }, $rowValues);
            $rows[] = '(' . implode(', ', $rowItems) . ')';
        }
        $query .= implode(', ', $rows);
        if ($return) {
            $query .= 'RETURNING *';
        }
        return $query;
    }
}
