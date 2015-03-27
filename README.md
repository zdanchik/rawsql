# rawsql
The simplest query builder

###usage:
```php
\RawSql\Connection::initialize([
'test_connection' => 'mysql://test:qwerty123@localhost/test?charset=utf8'
]);

// Seelct
RawSql::select(Connection::getInstance('test_connection'), '*')
->from('abs')
->andWhere('email = ?', "123@asdas.ss")
->andWhere("password = ?", "papapa")
->execute(true);

// Insert
$insert = Rawsql::insert(Connection::getInstance('test_connection'), 'abs', array('user_id', 'person_id', 'name'));
foreach($a as $b) {
  $insert->setValues($b['user_id'], $b['person_id'], $b['name']);
}
$insert->execute();

```
