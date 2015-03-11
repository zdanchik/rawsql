# rawsql
The simplest query builder

###usage:
```php
\RawSql\Connection::initialize([
'test_connection' => 'mysql://test:qwerty123@localhost/test?charset=utf8'
]);


RawSql::select(Connection::getInstance('test_connection'), '*')
->from('abs')
->andWhere('email = ?', "123@asdas.ss")
->andWhere("password = ?", "papapa")
->execute(true);
```
