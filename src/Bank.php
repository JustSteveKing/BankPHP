<?php

namespace JustSteveKing\Bank;

use PDO;
use PDOException;
use JustSteveKing\Bank\Exceptions\QueryException;
use JustSteveKing\Bank\Exceptions\TableNotDefined;
use JustSteveKing\Bank\Exceptions\FailedDatabaseConnection;

class Bank
{
    /**
     * The Database Object
     *
     * @var PDO
     */
    protected $db;

    /**
     * The Database Statistics
     *
     * @var array
     */
    protected array $stats;

    /**
     * Are the Database Statistics enabled
     *
     * @var bool
     */
    public bool $statsEnabled = false;

    /**
     * Show the Query ran if we get an error
     *
     * @var bool
     */
    public bool $showSql = true;

    /**
     * The currently selected table
     *
     * @var null|string
     */
    protected string $table = '';

    /**
     * The SELECT claus
     *
     * @var null|string
     */
    protected string $fields = '';
    
    /**
     * The WHERE claus
     *
     * @var null|string
     */
    protected string $where = '';

    /**
     * The JOIN clause
     *
     * @var null|string
     */
    protected string $joins = '';

    /**
     * The ORDER BY clause
     *
     * @var null|string
     */
    protected string $order = '';

    /**
     * The GROUP BY clause
     *
     * @var null|string
     */
    protected string $groups = '';

    /**
     * The HAVING claus
     *
     * @var null|string
     */
    protected string $having = '';

    /**
     * The DISCTINCT clause
     *
     * @var null|string
     */
    protected string $distinct = '';

    /**
     * The LIMIT clause
     *
     * @var null|string
     */
    protected string $limit = '';

    /**
     * The OFFSET clause
     *
     * @var null|string
     */
    protected string $offset = '';

    /**
     * The configuration settings
     *
     * @var array
     */
    protected array $settings;

    /**
     * The PDO specific options
     *
     * @var array
     */
    protected array $options;

    /**
     * The SQL Statement
     *
     * @var string
     */
    protected string $sql = '';

    /**
     * The last Query Time
     *
     * @var int
     */
    protected int $queryTime;

    /**
     * The number of Rows from Query
     *
     * @var int
     */
    protected int $numRows = 0;

    /**
     * The number of Affected Rows from Query
     *
     * @var int
     */
    protected int $affectedRows = 0;

    /**
     * The last inserted ID from Query
     *
     * @var int
     */
    protected int $insertID = -1;

    /**
     * The class to convert query to
     */
    protected string $class = '';

    public function __construct(array $settings)
    {
        $this->settings = array_merge(
            [
            'host' => '127.0.0.1',
            'port' => null,
            'database' => 'database',
            'username' => 'username',
            'password' => 'password',
            ],
            $settings
        );


        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_CASE => PDO::CASE_NATURAL,
            PDO::ATTR_ORACLE_NULLS => PDO::NULL_EMPTY_STRING
        ];

        $this->buildConnection();
    }

    public function getDb(): ?PDO
    {
        return $this->db;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getFields(): ?string
    {
        return $this->fields;
    }

    public function class(string $class): self
    {
        if (is_string($class)) {
            $this->class = get_class($class);
        }

        return $this;
    }

    public function reset(): void
    {
        $this->table = '';
        $this->limit = '';
        $this->offset = '';
    }

    public function from(string $table, bool $reset = null): self
    {
        $this->table = $table;

        if ($reset) {
            $this->reset();
        }

        return $this;
    }

    public function get(): ?array
    {
        if (is_null($this->db)) {
            throw new DatabaseUndefined();
        }

        $result = null;

        if ($this->statsEnabled) {
            if (empty($this->stats)) {
                $this->stats = [
                    'queries' => []
                ];
            }

            $this->queryTime = microtime(true);
        }

        if (! is_null($this->sql)) {
            try {
                $error = null;
                $result = $this->db->prepare($this->sql);

                if (is_null($result)) {
                    $error = $this->db->errorInfo();
                } else {
                    ($this->class) ?
                        $result->setFetchMode(PDO::FETCH_CLASS, $this->class) :
                        $result->setFetchMode(PDO::FETCH_OBJ);

                    $result->execute();

                    $this->numRows = $result->rowCount();
                    $this->affectedRows = $result->rowCount();
                    $this->insertID = $this->db->lastInsertId();
                }
            } catch (PDOException $ex) {
                $error = $ex->getMessage();
            }

            if (! is_null($error)) {
                if ($this->showSql) {
                    $error .= "\nSQL: " . $this->sql;
                }
                throw new QueryException("Database error: $error");
            }
        }

        if ($this->statsEnabled) {
            $time = microtime(true) - $this->queryTime;
            array_push(
                $this->stats['queries'],
                [
                    'query' => $this->sql,
                    'time' => $time,
                    'rows' => (int)$this->numRows,
                    'changes' => (int)$this->affectedRows
                ]
            );
        }

        return $result->fetchAll();
    }

    public function sql($sql = null): object
    {
        if (! is_null($sql)) {
            $this->sql = trim((is_array($sql)) ? array_reduce($sql, [$this, 'build']) : $sql);

            return $this;
        }

        return $this->sql;
    }

    public function select($fields = '*', int $limit = null, int $offset = null): self
    {
        $this->checkTable();

        $this->fields = (is_array($fields)) ? implode(',', $fields) : $fields;
        $this->limit($limit, $offset);

        $this->sql(
            [
            'SELECT',
            $this->distinct,
            $this->fields,
            'FROM',
            $this->table,
            $this->joins,
            $this->where,
            $this->groups,
            $this->having,
            $this->order,
            $this->limit,
            $this->offset
            ]
        );

        return $this;
    }

    public function limit(int $limit = null, int $offset = null): self
    {
        if (! is_null($limit)) {
            $this->limit = "LIMIT $limit";
        }
        if (! is_null($offset)) {
            $this->offset($offset);
        }

        return $this;
    }

    public function offset(int $offset, int $limit = null): self
    {
        if (! is_null($offset)) {
            $this->offset = "OFFSET $offset";
        }
        if (! is_null($limit)) {
            $this->limit($limit);
        }

        return $this;
    }

    public function order(string $field, string $direction = 'ASC'): object
    {
        $join = (empty($this->order)) ? 'ORDER BY' : ',';

        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $field[$key] = "$value $direction";
            }
        } else {
            $field .= " $direction";
        }

        $fields = (is_array($field)) ? implode(', ', $field) : $field;

        $this->order .= "$join $fields";

        return $this;
    }

    public function distinct(bool $value = true): self
    {
        $this->distinct = ($value) ? 'DISTINCT' : '';

        return $this;
    }

    public function build(string $sql = null, string $input): string
    {
        return (strlen($input) > 0) ? ($sql . ' ' . $input) : $sql;
    }

    public function getStats(): array
    {
        $this->stats['total_time'] = 0;
        $this->stats['num_queries'] = 0;
        $this->stats['num_rows'] = 0;
        $this->stats['num_changes'] = 0;

        if (isset($this->stats['queries'])) {
            foreach ($this->stats['queries'] as $query) {
                $this->stats['total_time'] += $query['time'];
                $this->stats['num_queries'] += 1;
                $this->stats['num_rows'] += $query['rows'];
                $this->stats['num_changes'] += $query['changes'];
            }
        }

        $this->stats['avg_query_time'] =
            $this->stats['total_time'] /
            (float)(($this->stats['num_queries'] > 0) ? $this->stats['num_queries'] : 1);

        return $this->stats;
    }

    protected function checkTable()
    {
        if (is_null($this->table)) {
            throw new TableNotDefined();
        }
    }

    protected function buildConnection(): void
    {
        try {
            $this->db = new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s',
                    $this->settings['host'],
                    $this->settings['port'] ?? 3306,
                    $this->settings['database']
                ),
                $this->settings['username'],
                $this->settings['password'],
                $this->options
            );
        } catch (PDOException $e) {
            throw new FailedDatabaseConnection($e->getMessage());
        }
    }
}
