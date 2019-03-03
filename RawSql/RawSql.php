<?php
/**
 * Created by JetBrains PhpStorm.
 * User: azhdanov
 * Date: 22.07.13
 * Time: 21:09
 * To change this template use File | Settings | File Templates.
 */


namespace RawSql;

use RawSql\Action\RawSqlDelete;
use RawSql\Action\RawSqlSelect;
use RawSql\Action\RawSqlInsert;
use RawSql\Action\RawSqlUpdate;

abstract class RawSql {

  protected $index_name;
  protected $options = array();
  protected $_conn = null;
  protected $where_parts = array();
  protected $args = array();

  abstract public function getSql();

  /**
   * @param $connection
   * @param null $select
   * @return RawSqlSelect
   */
  public static function select($connection, $select = null)
  {
    return new RawSqlSelect($connection, $select);
  }

  /**
   * @param $connection
   * @param null $table
   * @param array $columns
   * @return RawSqlInsert
   */
  public static function insert($connection, $table, $columns = null, $quoteColumns = true)
  {
    return new RawSqlInsert($connection, $table, $columns, $quoteColumns);
  }

  /**
   * @param $connection
   * @param null $update
   * @return RawSqlUpdate
   */
  public static function update($connection, $index_name = null)
  {
    return new RawSqlUpdate($connection, $index_name);
  }

  /**
   * @param $connection
   * @param $table
   */
  public static function delete($connection)
  {
    return new RawSqlDelete($connection);
  }

  public function __toString()
  {
    return $this->getSql();
  }

  public function showQuery()
  {
    $keys = array();
    $values = array();
    foreach ($this->getArgs() as $key=>$value) {
      if (is_string($key)) {
        $keys[] = '/:'.$key.'/';
      } else {
        $keys[] = '/[?]/';
      }
      if(is_int($value) || is_float($value)) {
        $values[] = $value;
      } else {
        $values[] = $this->getConnection()->quote($value);
      }
    }
    $query = preg_replace($keys, $values, $this, 1, $count);
    return $query;
  }

  protected function processStmtArguments($params)
  {
    // first parameter is query part
    $query_part = array_shift($params);
    // send parameter may be array with params or may not to be array
    if (isset($params[0]) && is_array($params[0])) {
      $params = $params[0];
    }
    $this->where_parts[] = $query_part;

    foreach ($params as $param) {
      $this->args[] = $param;
    }
  }

  /**
   * @return Connection
   */
  public function getConnection()
  {
    return $this->_conn;
  }

  /**
   * return arguments of query
   * @return array
   */
  protected function getArgs()
  {
    return $this->args;
  }

}
