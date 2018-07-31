<?php
/**
 * Created by JetBrains PhpStorm.
 * User: azhdanov
 * Date: 23.07.13
 * Time: 10:34
 * To change this template use File | Settings | File Templates.
 */
namespace RawSql;


class RawArray extends \ArrayObject {

  public function __construct($array = array())
  {
    parent::__construct($array, \ArrayObject::STD_PROP_LIST);
  }

  public function isEmpty()
  {
    return !$this->getArrayCopy();
  }


  public function parse($field = null, $group = null, $containedGroup = false)
  {

    $ret = array();
    $group = is_string($group) ? array($group) : (is_array($group) ? $group : []);
    $containedGroup = ($group) ? $containedGroup : true;
    $groupCount = count($group);

    foreach ($this->getArrayCopy() AS $row) {
      /**
       * подготавливаем место($place), куда поставить новую порцию данных
       */
      $place = & $ret;

      for ($i = 0; $i < $groupCount; $i++) {
        $place[$row[$group[$i]]] = (isset($place[$row[$group[$i]]])) ? $place[$row[$group[$i]]] : array();
        $place = & $place[$row[$group[$i]]];
      }

      /**
       * место готово ($place) - теперь определяем, что оставить от строки($row) из БД
       * typeof $field = array|string|null
       */
      if (is_string($field)) {
        if (isset($row[$field]))
          $add = $row[$field];
        else
          $add = null;
      } elseif (is_array($field)) {
        foreach ($field as $needField) {
          if (isset($row[$needField])) {
            $add[$needField] = $row[$needField];
          } else {
            $add[$needField] = null;
          }
        }
      } else {
        $add = $row;
      }
      if ($containedGroup) {
        $place[] = $add;
      } else {
        $place = $add;
      }
    }
    return $ret;
  }

  public function getFirst() {
    foreach ($this->getArrayCopy() as $v){
      return $v;
    }
    return null;
  }

}