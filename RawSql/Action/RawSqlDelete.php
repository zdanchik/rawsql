<?php
namespace RawSql\Action;
use RawSql\RawSql;

/**
 * Created by JetBrains PhpStorm.
 * User: azhdanov
 * Date: 22.07.13
 * Time: 21:09
 * To change this template use File | Settings | File Templates.
 */

class RawSqlDelete extends RawSql {

  protected $_cached = false;
  protected $_cached_time = null;
  protected $order;
  protected $group_by;

  /**
   * @param $connection
   * @return RawSqlDelete
   */
  function __construct($connection)
  {
    $this->_conn = $connection;
    return $this;
  }

  /**
   * @param $index_name
   * @return RawSqlDelete
   */
  public function from($index_name)
  {
    $this->index_name = $index_name;
    return $this;
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
   * @return RawSqlDelete
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
   * @return RawSqlDelete
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
   * @return RawSqlDelete
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
   * @param $from
   * @param $to
   *
   * @return $this
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

  protected $limit;

  /**
   * @param $value
   * @return RawSqlDelete
   */
  public function limit($value)
  {
    $this->limit = $value;
    return $this;
  }

  /**
   * @param $value
   * @return RawSqlDelete
   */
  public function order($value)
  {
    $this->order = $value;
    return $this;
  }


  protected $offset;

  /**
   * @param $offset
   * @return RawSqlDelete
   */
  public function offset($offset)
  {
    $this->offset = $offset;
    return $this;
  }

  protected function getLimitSql()
  {
    return isset($this->limit) && $this->limit ? "LIMIT {$this->limit}" : "";
  }

  protected function getOrderSql()
  {
    return isset($this->order) && $this->order ? "ORDER BY {$this->order}" : "";
  }

  protected function getOffsetSql()
  {
    return isset($this->offset) && $this->offset ? "OFFSET {$this->offset}" : "";
  }


  /**
   * @return string
   */
  public function getSql()
  {
    $sql_arr = array (
      "DELETE FROM ". $this->index_name
    );
    if ($this->getWhereSql()) {
      array_push($sql_arr, "WHERE {$this->getWhereSql()}");
    }

    if ($this->getOrderSql()) {
      array_push($sql_arr, "{$this->getOrderSql()}");
    }

    if ($this->getLimitSql()) {
      array_push($sql_arr, "{$this->getLimitSql()}");
    }

    if ($this->getOffsetSql()) {
      array_push($sql_arr, "{$this->getOffsetSql()}");
    }
    return implode(" ", $sql_arr);
  }

  /**
   * @param $connection
   * @return bool
   */
  const CACHE_PREFIX = 'raw_sql';
  public function execute($one = false)
  {
    if ($one) {
      $this->limit(1);
    }
    $statement = $this->getConnection()->prepare($this);
    $statement->execute($this->args);
    if ($this->getConnection()->errorCode() == \PDO::ERR_NONE) {
      return true;
    }
    return false;
  }

}