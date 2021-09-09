<?php

namespace Quangphuc\QueryFactory;

use Quangphuc\QueryFactory\ParamType\ParamType;

class QueryFactoryHelper {
    /**
     * @param array  $where
     * @param string $operator AND | OR | and | or
     *
     * @return string
     * @throws QueryFactoryException
     */
    static function parseWhereClause(array $where, $operator = 'AND') {
        $items = [];
        foreach ($where as $key => $value) {
            if (in_array($key, ['and', 'or', 'AND', 'OR'])) {
                $items[] = self::parseWhereClause($value, $key);
                continue;
            }
            preg_match('/^(.*?)(>|>=|<|<=|=|!=)?$/', $key, $matches);
            $key = $matches[1];
            $op = !empty($matches[2]) ? $matches[2] : '=';
            if (is_array($value)) {
                $values = [];
                foreach ($value as $item) {
                    $values[] = self::escapeValue($item);
                }
                $items[] = self::escapeIdentifier($key) . " IN (" . implode(', ', $values) . ')';
            } else {
                $items[] = self::escapeIdentifier($key) . " $op " . self::escapeValue($value);
            }
        }
        return '(' . implode(" $operator ", $items) . ')';
    }

    /**
     * @param mixed  $value
     * @param string $type text | integer | float | date | timestamp | timestamptz | 'json' | 'jsonb'
     */
    static function escapeValue($value, $type = NULL) {
        if (is_null($value)) {
            return 'NULL';
        }
        if ($value instanceof ParamType) {
            return $value();
        }
        if (is_array($value) && !self::isAssoc($value)) {
            $values = array_map(function ($value) use ($type) {
                return self::escapeValue($value, $type);
            }, $value);
            return 'ARRAY[' . implode(', ', $values) . ']';
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        switch ($type) {
            case 'integer':
                $raw = (int) $value;
                break;
            case 'float':
                $raw = (float) $value;
                break;
            default:
                $value = (string) $value;
                $value = str_replace("'", "''", $value);
                $raw = "'{$value}'";
        }
        if ($type) {
            return "CAST($raw AS $type)";
        }
        return $raw;
    }

    /**
     * @param string $identifier
     */
    static function escapeIdentifier($identifier) {
        $field = str_replace('"', '', $identifier);
        return "\"$field\"";
    }

    /**
     * @param mixed $value
     */
    static function isAssoc($value) {
        if (!is_array($value) || array() === $value) {
            return false;
        }
        return array_keys($value) !== range(0, count($value) - 1);
    }

    /**
     *
     */
    static function randomTextTag($length = 5, $pool = '_0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $charactersLength = strlen($pool);
        $tag = '';
        for ($i = 0; $i < $length; $i++) {
            $tag .= $pool[rand(0, $charactersLength - 1)];
        }
        return $tag;
    }
}
