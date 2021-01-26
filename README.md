# Hector Connection

**Hector Connection** is the PDO connection module of Hector ORM. Can be used independently of ORM.

## Installation

### Composer

You can install **Hector Connection** with [Composer](https://getcomposer.org/), it's the recommended installation.

```bash
$ composer require hectororm/connection
```

### Dependencies

* **PHP** ^8.0
* PHP extensions:
    * **PDO**

## Usage

### Create a connection

The connection information must be pass to DSN format, with password inside.

To create a simple connection:

```php
use Hector\Connection\Connection;

$connection = new Connection('mysql:host:...');
```

You can create a complex connection with read/write ways:

```php
use Hector\Connection\Connection;

$connection = new Connection(dsn: 'mysql:host:...', readDsn: 'mysql:host:...');
```

### Query

#### Execute

Execute a statement and get the number of affected rows.

```php
use Hector\Connection\Connection;

$connection = new Connection('mysql:host:...');
$nbAffectedRows = $connection->execute('UPDATE `table` SET `foo` = ? WHERE `id` = ?', ['bar', 1]);
```

The method execute queries on write DSN.

#### Fetch

Three methods are available to fetch results. When multiple results, methods returns a `Generator`.

You can pass parameters for statement to the second argument of methods.

All fetch methods are done on read DSN.

##### Fetch one

Fetch one result from statement.
`NULL` returned if no result.

```php
use Hector\Connection\Connection;

$connection = new Connection('mysql:host:...');
$row = $connection->fetchOne('SELECT * FROM `table` WHERE `id` = ?', [1]);
```

##### Fetch all

Fetch all result from statement.

```php
use Hector\Connection\Connection;

$connection = new Connection('mysql:host:...');
$rows = $connection->fetchAll('SELECT * FROM `table` WHERE `column` = :bar', ['bar' => 'foo']);

foreach($rows as $row) {
    // ...
}
```

##### Fetch column

Fetch only one column of all result from statement.

```php
use Hector\Connection\Connection;

$connection = new Connection('mysql:host:...');
$columns = $connection->fetchColumn('SELECT * FROM `table` WHERE `column` = :bar', ['bar' => 'foo'], 2);

foreach($columns as $column) {
    // ...
}
```

#### Last insert ID

You can retrieve the last insert ID with method `getLastInsertId()`.

```php
use Hector\Connection\Connection;

$connection = new Connection('mysql:host:...');
$nbAffectedRows = $connection->execute('INSERT INTO `table` ...');
$lastInsertId = $connection->getLastInsertId();
```

#### Transactions

`Connection` class has 4 methods to play with transactions:

- `Connection::beginTransaction(): void`
- `Connection::commit(): void`
- `Connection::rollBack(): void`
- `Connection::inTransaction(): bool`

If you begin a new transaction even though a transaction has already started, the new transaction will be ignored, and
it needed to call even times the `commit` or `rollBack` methods.

### Connection set

You can create a set of `Connection` objects with `ConnectionSet` class.
It needed to name different connections, the default name is store in constant `Connection::DEFAULT_NAME`.

```php
use Hector\Connection\Connection;
use Hector\Connection\ConnectionSet;

$connectionSet = new ConnectionSet();
$connectionSet->addConnection(new Connection('mysql:host:...'));
$connectionSet->addConnection(new Connection('mysql:host:...', name: 'foo'));

$connectionSet->hasConnection(); // TRUE
$connectionSet->hasConnection(Connection::DEFAULT_NAME); // TRUE
$connectionSet->hasConnection('foo'); // TRUE
$connectionSet->hasConnection('bar'); // FALSE

$connectionSet->getConnection(); // FIRST CONNECTION
$connectionSet->getConnection(Connection::DEFAULT_NAME); // FIRST CONNECTION
$connectionSet->getConnection('foo'); // SECOND CONNECTION
$connectionSet->getConnection('bar'); // THROW NotFoundException EXCEPTION
```

### Logger

A logger is available to log queries.

```php
use Hector\Connection\Connection;
use Hector\Connection\Log\LogEntry;
use Hector\Connection\Log\Logger;

$logger = new Logger();
$connection = new Connection('mysql:host:...', logger: $logger);

/** @var LogEntry[] $logEntries */
$logEntries = $logger->getLogs();
```