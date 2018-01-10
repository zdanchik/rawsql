<?php
/**
 * Created by JetBrains PhpStorm.
 * User: azhdanov
 * Date: 22.07.13
 * Time: 21:09
 * To change this template use File | Settings | File Templates.
 */
namespace RawSql\Action;

use RawSql\Cache;
use RawSql\RawArray;
use RawSql\RawSql;

class RawSqlSelect extends RawSql {
  protected $select = '*';

  protected $_cached = false;
  protected $_cached_time = null;
  protected $order;
  protected $group_by;
  protected $having;

  /**
   * @param $connection
   * @param null $select
   * @return RawSqlSelect
   */
  function __construct($connection, $select = null)
  {
    $this->_conn = $connection;
    if ($select) {
      $this->setSelectFields($select);
    }
    return $this;
  }

  /**
   * @param int $cached_time
   * @return RawSqlSelect
   */
  public function setCached($cached_time = 800)
  {
    if(!empty($cached_time)) {
      $this->_cached = true;
      $this->_cached_time = $cached_time;
    }
    return $this;
  }

  /**
   * @param $index_name
   * @return RawSqlSelect
   */
  public function from($index_name)
  {
    $this->index_name = $index_name;
    return $this;
  }

  public function getFrom()
  {
    return $this->index_name;
  }

  /**
   * @return RawSqlSelect
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
   * @return RawSqlSelect
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
   * @return RawSqlSelect
   */
  public function andWhereIn($field, array $values)
  {
    if (count($values)) {
      $this->andWhere("{$field} IN (". implode(', ', array_fill(0, count($values), '?')).")", $values);
    } else {
      throw new \Exception('Invalid count of elements in query');
    }
    return $this;
  }

  public function count()
  {
    return count($this->execute());
  }


  /**
   * @param $field
   * @param array $range = array (0 => from, 1 => to)
   * @return RawSqlSelect
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
   * @return RawSqlSelect
   */
  public function limit($value)
  {
    $this->limit = $value;
    return $this;
  }

  protected $offset;

  /**
   * @param $offset
   * @return RawSqlSelect
   */
  public function offset($offset)
  {
    $this->offset = $offset;
    return $this;
  }

  public function getLimit()
  {
    return $this->limit;
  }
  public function getOffset()
  {
    return $this->offset;
  }

  protected function getLimitSql()
  {
    return isset($this->limit) && $this->limit ? "LIMIT {$this->limit}" : "";
  }

  protected function getOffsetSql()
  {
    return isset($this->offset) && $this->offset ? "OFFSET {$this->offset}" : "";
  }

   /**
   * @return RawSqlSelect
   */
  public function orderBy($order)
  {
    $this->order = $order;
    return $this;
  }

  /**
   * @return string
   */
  protected function getOrderSql()
  {
    return isset($this->order) && $this->order ? "ORDER BY {$this->order}" : "";
  }

  /**
   * @return RawSqlSelect
   */
  public function groupBy($column)
  {
    $this->group_by = $column;
    return $this;
  }

  /**
   * @return string
   */
  protected function getGroupBySql()
  {
    return isset($this->group_by) && $this->group_by ? "GROUP BY {$this->group_by}" : "";
  }

  /**
   * @return RawSqlSelect
   */
  public function having($value)
  {
    $this->having = $value;
    return $this;
  }

  /**
   * @return string
   */
  protected function getHavingSql()
  {
    return isset($this->having) && $this->having ? "HAVING {$this->having}" : "";
  }

  /**
   * @return string
   */
  public function getSql()
  {
    $sql_arr = array (
      "SELECT {$this->select}",
      "FROM {$this->index_name}"
    );
    if ($this->getWhereSql()) {
      array_push($sql_arr, "WHERE {$this->getWhereSql()}");
    }
    if ($this->getGroupBySql()) {
      array_push($sql_arr, "{$this->getGroupBySql()}");
    }
    if ($this->getHavingSql()) {
      array_push($sql_arr, "{$this->getHavingSql()}");
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
   * @return RawArray
   */
  const CACHE_PREFIX = 'raw_sql';
  public function execute($one = false)
  {
    $data = array();
    if ($this->_conn) {
      if ($this->_cached) {
        $key  = self::CACHE_PREFIX . $this->_conn .  md5($this . $one . implode(',', $this->getArgs()));
        $data = $this->getCache()->get([$key])->getData();
        if (!$data) {
          $data = $this->getData($one);
          if ($data) {
            $this->getCache()->set([$key], serialize($data), $this->_cached_time);
          }
        } else {
          $data = unserialize($data);
        }
      } else {
        $data  = $this->getData($one);
      }
    }
    return new RawArray($data ? $data : array());
  }

  private function getData($one) {
    return $one ? $this->fetchOne() : $this->fetchAll();
  }

  public function fetch($callback) {
    $stmt = $this->getPreparedStmt();
    while ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
      $callback($data);
    }
  }

  private function fetchAll() {
    $stmt = $this->getPreparedStmt();
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }

  private function fetchOne() {
    $stmt = $this->getPreparedStmt();
    return $stmt->fetch(\PDO::FETCH_ASSOC);
  }

  /**
   * @return null|\PDOStatement
   */
  public function getPreparedStmt() {
    /**
     * @var $statement \PDOStatement
     */
    $statement = $this->getConnection()->prepare($this);
    try {
      $statement->execute($this->getArgs());
    } catch (\PDOException $e) {
      /**
       * Solve 2006 MySQL error
       */
      $this->getConnection()->reconnect();
      $statement = $this->getConnection()->prepare($this);
      $statement->execute($this->getArgs());
    }
    if ($this->getConnection()->errorCode() == \PDO::ERR_NONE) {
      return $statement;
    }
    return null;
  }




  //protected $cache;
  public function getCache()
  {
    return Cache::getInstance('rawsql_select');
  }

  public function setSelectFields($fields)
  {
    $this->select = $fields;
    return $this;
  }

}
