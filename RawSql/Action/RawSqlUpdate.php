<?php
namespace RawSql\Action;
use RawSql\RawArray;
use RawSql\RawSql;

/**
 * Created by JetBrains PhpStorm.
 * User: azhdanov
 * Date: 22.07.13
 * Time: 21:09
 * To change this template use File | Settings | File Templates.
 */

class RawSqlUpdate extends RawSql {
  protected $update = null;
  protected $update_args = array();


  /**
   * @param $connection
   * @param null $update
   * @return RawSqlUpdate
   */
  function __construct($connection, $index_name = null)
  {
    $this->_conn = $connection;
    if ($index_name) {
      $this->index_name = $index_name;
    }
    return $this;
  }

  /**
   * @param $index_name
   * @return RawSqlUpdate
   */
  public function set()
  {
    $params = func_get_args();
    $this->processUpdateArguments($params);
    return $this;
  }

  public function setArray($params, $quote = true)
  {
    $i = 0;
    $q = null;
    if ($quote) {
      $q = "`";
    }
    foreach($params as $key => $value) {
      $i++;
      $str = null;
      if (!is_null($value)) {
        $this->update_args[] = $value;
        $str = "{$q}{$key}{$q} = ?";
      } else {
        $str = "{$q}{$key}{$q} = NULL";
      }
      if (count($params) == $i - 1) {
        $str .= ",";
      }
      $this->update[] = $str;
    }
    return $this;
  }


  private function processUpdateArguments($params)
  {
    // first parameter is query part
    $query_part = array_shift($params);
    $this->update[] = $query_part;
    // send parameter may be array with params or may not to be array
    if (isset($params[0]) && is_array($params[0])) {
      $params = $params[0];
    }
    foreach ($params as $param) {
      $this->update_args[] = $param;
    }
  }

  /**
   * @return RawSqlUpdate
   */
  public function andWhere()
  {
    $params = func_get_args();
    $this->processStmtArguments($params);
    return $this;
  }

  /**
   * @param $field
   * @param array $values
   * @return RawSqlUpdate
   */
  public function andWhereNotIn($field, array $values)
  {
    if (count($values)) {
      $this->andWhere("{$field} NOT IN (". implode(', ', array_fill(0, count($values), '?')).")", $values);
    }
    return $this;
  }

  /**
   * @param $field
   * @param array $values
   * @return RawSqlUpdate
   */
  public function andWhereIn($field, array $values)
  {
    if (count($values)) {
      $this->andWhere("{$field} IN (". implode(', ', array_fill(0, count($values), '?')).")", $values);
    }
    return $this;
  }

  /**
   * @param $field
   * @param array $range = array (0 => from, 1 => to)
   * @return RawSqlUpdate
   */
  public function addWhereInRange($field, $from, $to)
  {
    $this->andWhere($to ? "{$field} BETWEEN {$from} AND {$to}" : "{$field} >= {$from}");
    return $this;
  }

  protected function getWhereParts()
  {
    return $this->where_parts;
  }

  protected function getWhereSql()
  {
    return implode(' AND ', $this->getWhereParts());
  }

  protected function getUpdateParts()
  {
    return $this->update;
  }

  protected function getUpdateSql()
  {
    return implode(', ', $this->getUpdateParts());
  }

  /**
   * @return string
   */
  public function getSql()
  {
    $sql_arr = array (
      "UPDATE {$this->index_name}",
      "SET {$this->getUpdateSql()}",
    );

    if(count($this->getWhereParts())) {
      $sql_arr[] = "WHERE {$this->getWhereSql()}";
    }

    return implode(" ", $sql_arr);
  }

  /**
   * @param $connection
   * @return RawArray
   */
  public function execute()
  {
    if ($this->_conn) {
      $a         = microtime(true);
      $statement = $this->getConnection()->prepare($this);
      $res       = $statement->execute($this->getArgs());
      $b         = microtime(true);
      //if (sfConfig::get('sf_web_debug')) {
      //  sfContext::getInstance()->getLogger()->log('{RawSql}' . $this->showQuery() . ' time: ' . ($b - $a));
      //}

      return $res;
    }
    return false;
  }

  protected function getArgs(){
    return array_merge_recursive($this->update_args, parent::getArgs());
  }
}
