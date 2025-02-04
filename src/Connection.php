<?php
/*
 * This file is part of Hector ORM.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Hector\Connection;

use Exception;
use Generator;
use Hector\Connection\Bind\BindParamList;
use Hector\Connection\Driver\DriverInfo;
use Hector\Connection\Exception\ConnectionException;
use Hector\Connection\Log\LogEntry;
use Hector\Connection\Log\Logger;
use PDO;
use PDOStatement;

class Connection
{
    public const DEFAULT_NAME = 'default';

    private ?PDO $pdo = null;
    private ?PDO $readPdo = null;
    private ?DriverInfo $driverInfo = null;
    private int $transactions = 0;

    /**
     * Connection constructor.
     *
     * @param string $dsn
     * @param string|null $username
     * @param string|null $password
     * @param string|null $readDsn
     * @param string $name
     * @param Logger|null $logger
     */
    public function __construct(
        protected string $dsn,
        private ?string $username = null,
        private ?string $password = null,
        protected ?string $readDsn = null,
        protected string $name = self::DEFAULT_NAME,
        protected ?Logger $logger = null
    ) {
    }

    /**
     * Create connection from PDO objects.
     *
     * @param PDO $pdo
     * @param PDO|null $readPdo
     * @param string $name
     * @param Logger|null $logger
     *
     * @return self
     */
    public static function fromPdo(
        PDO $pdo,
        ?PDO $readPdo = null,
        string $name = self::DEFAULT_NAME,
        ?Logger $logger = null,
    ): self {
        $connection = new self('PDO', name: $name, logger: $logger);
        $connection->pdo = $pdo;
        $connection->readPdo = $readPdo;

        return $connection;
    }

    /**
     * PHP serialize method.
     *
     * @return array
     * @throws ConnectionException
     */
    public function __serialize(): array
    {
        $this->dsn === 'PDO' && throw new ConnectionException('Connection created from PDO');

        return [
            'dsn' => $this->dsn,
            'username' => $this->username,
            'password' => $this->password,
            'readDsn' => $this->readDsn,
            'name' => $this->name,
            'logger' => $this->logger,
        ];
    }

    /**
     * PHP unserialize method.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->dsn = $data['dsn'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->readDsn = $data['readDsn'];
        $this->name = $data['name'];
        $this->logger = $data['logger'];
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get logger.
     *
     * @return Logger|null
     */
    public function getLogger(): ?Logger
    {
        return $this->logger;
    }

    /**
     * Get PDO.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        if (null !== $this->pdo) {
            return $this->pdo;
        }

        $logEntry = $this->logger?->newEntry($this->name, 'CONNECTION ' . $this->dsn, type: LogEntry::TYPE_CONNECTION);

        $this->pdo = new PDO($this->dsn, $this->username, $this->password);

        $logEntry?->end();

        return $this->pdo;
    }

    /**
     * Get read only PDO.
     *
     * @return PDO
     */
    public function getReadPdo(): PDO
    {
        // Return read/write PDO if a transaction started
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if (null !== $this->readPdo) {
            return $this->readPdo;
        }

        if (null === $this->readDsn) {
            return $this->getPdo();
        }

        $logEntry = $this->logger?->newEntry($this->name, 'CONNECTION ' . $this->readDsn);

        $this->readPdo = new PDO($this->readDsn, $this->username, $this->password);

        $logEntry?->end();

        return $this->readPdo;
    }

    /**
     * Get driver name.
     *
     * @return string
     * @deprecated 1.0.0-beta8 No longer used by internal code and not recommended
     * @see Connection::getDriverInfo()
     */
    public function getDriverName(): string
    {
        return $this->getDriverInfo()->getDriver();
    }

    /**
     * Get driver info.
     *
     * @return DriverInfo
     */
    public function getDriverInfo(): DriverInfo
    {
        return $this->driverInfo ??= DriverInfo::fromPDO($this->getPdo());
    }

    /**
     * Get last insert id.
     *
     * @param string|null $name
     *
     * @return string
     */
    public function getLastInsertId(?string $name = null): string
    {
        return $this->getPdo()->lastInsertId($name);
    }

    /**
     * Begin transaction.
     */
    public function beginTransaction(): void
    {
        $this->transactions++;

        if ($this->transactions === 1) {
            $this->getPdo()->beginTransaction();
        }
    }

    /**
     * Commit transaction.
     */
    public function commit(): void
    {
        if ($this->transactions <= 0) {
            return;
        }

        if ($this->transactions === 1) {
            $this->getPdo()->commit();
        }

        $this->transactions--;
    }

    /**
     * Roll back transaction.
     */
    public function rollBack(): void
    {
        if ($this->transactions > 0) {
            $this->getPdo()->rollBack();
            $this->transactions = 0;
        }
    }

    /**
     * In transaction?
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->getPdo()->inTransaction();
    }

    /**
     * PDO execute.
     *
     * @param PDO $pdo
     * @param string $statement
     * @param BindParamList|array $input_parameters
     *
     * @return PDOStatement
     */
    protected function pdoExecute(PDO $pdo, string $statement, BindParamList|array $input_parameters = []): PDOStatement
    {
        is_array($input_parameters) && $input_parameters = new BindParamList($input_parameters);
        $logEntry = $this->logger?->newEntry(
            $this->name,
            $statement,
            $input_parameters,
            (new Exception())->getTraceAsString()
        );

        try {
            $stm = $pdo->prepare($statement);

            foreach ($input_parameters as $parameter) {
                $stm->bindValue(
                    $parameter->getName(),
                    $parameter->getValue(),
                    $parameter->getDataType()
                );
            }

            $stm->execute();
        } finally {
            $logEntry?->end();
        }

        return $stm;
    }

    /**
     * Execute.
     *
     * @param string $statement
     * @param BindParamList|array $input_parameters
     *
     * @return int
     * @see PDOStatement::execute
     */
    public function execute(string $statement, BindParamList|array $input_parameters = []): int
    {
        $stm = $this->pdoExecute($this->getPdo(), $statement, $input_parameters);

        return $stm->rowCount();
    }

    /**
     * Fetch all.
     *
     * @param string $statement
     * @param BindParamList|array $input_parameters
     *
     * @return array<array>
     * @see PDOStatement::fetchAll
     */
    public function fetchAll(string $statement, BindParamList|array $input_parameters = []): array
    {
        $stm = $this->pdoExecute($this->getReadPdo(), $statement, $input_parameters);

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Yield all.
     *
     * @param string $statement
     * @param BindParamList|array $input_parameters
     *
     * @return Generator<array>
     */
    public function yieldAll(string $statement, BindParamList|array $input_parameters = []): Generator
    {
        $stm = $this->pdoExecute($this->getReadPdo(), $statement, $input_parameters);

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * Fetch one.
     *
     * @param string $statement
     * @param BindParamList|array $input_parameters
     *
     * @return array|null
     * @see PDOStatement::fetch
     */
    public function fetchOne(string $statement, BindParamList|array $input_parameters = []): ?array
    {
        $stm = $this->pdoExecute($this->getReadPdo(), $statement, $input_parameters);

        return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Fetch column.
     *
     * @param string $statement
     * @param BindParamList|array $input_parameters
     * @param int $column
     *
     * @return array
     * @see PDOStatement::fetchColumn
     */
    public function fetchColumn(
        string $statement,
        BindParamList|array $input_parameters = [],
        int $column = 0
    ): array {
        $stm = $this->pdoExecute($this->getReadPdo(), $statement, $input_parameters);

        return $stm->fetchAll(PDO::FETCH_COLUMN, $column);
    }

    /**
     * Yield column.
     *
     * @param string $statement
     * @param BindParamList|array $input_parameters
     * @param int $column
     *
     * @return Generator
     * @see PDOStatement::fetchColumn
     */
    public function yieldColumn(
        string $statement,
        BindParamList|array $input_parameters = [],
        int $column = 0
    ): Generator {
        $stm = $this->pdoExecute($this->getReadPdo(), $statement, $input_parameters);

        while (false !== ($row = $stm->fetchColumn($column))) {
            yield $row;
        }
    }
}