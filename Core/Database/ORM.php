<?php

namespace Core\Database;

use InvalidArgumentException;
use PDO;
use PDOException;

class ORM
{
    private array $wheres = [];
    private array $params = [];
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private ?array $currentRow = null;

    public function __construct(
        private string $table,
        private PDO $connection
    ) {
        $this->table = $table;
        $this->connection = $connection;
    }

    public function all(array $params)
    {
        $values = !empty($params) ? implode(", ", $params) : "*";
        try {
            $query = "SELECT {$values} FROM {$this->table}";
            $stm = $this->connection->prepare($query);
            $stm->execute();
            return $stm->fetchAll();
        } catch (PDOException $e) {
            //error_log("Error en {$this->table}".$e->getMessage());
            throw $e;
        }
    }

    public function where(string $column, string $comparison, $value): self
    {
        $allowed = ['=', '!=', '<', '>', '<=', '>=', 'LIKE'];
        if (!in_array($comparison, $allowed, true)) {
            throw new InvalidArgumentException("Operador no permitido");
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Columna inválida");
        }

        $param = ":w" . count($this->wheres);
        $this->wheres[] = "{$column} {$comparison} {$param}";
        $this->params[$param] = $value;

        return $this;
    }

    public function paginate(int $page = 1, int $perPage = 15, array $columns = ['*']): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        $total = $this->count();

        $this->limit($perPage, $offset);
        $items = $this->get($columns);

        $lastPage = (int) ceil($total / $perPage);


        $this->reset();

        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
                'has_prev' => $page > 1,
                'has_next' => $page < $lastPage,
            ],
        ];
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($this->wheres) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }
        $stm = $this->connection->prepare($sql);
        $stm->execute($this->params);
        return (int) $stm->fetchColumn();
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function get(array $params = ["*"]): array
    {
        $values = !empty($params) ? implode(", ", $params) : "*";
        try {
            $sql = "SELECT {$values} FROM {$this->table}";

            if ($this->wheres) {
                $sql .= " WHERE " . implode(' AND ', $this->wheres);
            }

            if ($this->orders) {
                $sql .= " ORDER BY " . implode(', ', $this->orders);
            }

            if ($this->limit !== null) {
                $sql .= " LIMIT {$this->limit} OFFSET {$this->offset}";
            }

            $stm = $this->connection->prepare($sql);
            $stm->execute($this->params);
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en {$this->table}" . $e->getMessage());
            throw $e;
        }
    }

    public function first(array $params = ["*"]): ?array
    {
        $results = $this->get($params);
        return $results[0] ?? null;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Columna inválida: {$column}");
        }

        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException("Dirección inválida: {$direction}");
        }

        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    public function find($id, array $params = ["*"])
    {
        $values = isset($params) ? implode(', ', $params) : "*";
        try {
            $query = "SELECT {$values} FROM {$this->table} WHERE id =:id";
            $stm = $this->connection->prepare($query);
            $stm->bindValue(":id", $id);
            $stm->execute();
            return $stm->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (PDOException $e) {
            error_log("Error en {$this->table}" . $e->getMessage());
            throw $e;
        }
    }

    public function load($id): ?self
    {
        $row = $this->find($id);
        if (!$row)
            return null;

        $this->currentRow = $row;
        return $this;
    }

    public function getId()
    {
        return $this->currentRow['id'] ?? null;
    }

    public function toArray(): array
    {
        return $this->currentRow;
    }

    public function create($data)
    {
        try {
            $keys = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));
            $query = "INSERT INTO {$this->table} ({$keys}) VALUES ({$values})";

            $stm = $this->connection->prepare($query);

            foreach ($data as $key => $value) {
                $stm->bindValue(":{$key}", $value);
            }
            return $stm->execute();

        } catch (PDOException $e) {
            error_log("Error en {$this->table}" . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, $data)
    {
        try {
            $query = "UPDATE {$this->table} SET ";
            foreach ($data as $key => $value) {
                $query .= "{$key} = :{$key},";
            }
            $query = trim($query, ",");
            $query .= " WHERE id =:id";

            $stm = $this->connection->prepare($query);
            foreach ($data as $key => $value) {
                $stm->bindValue(":{$key}", $value);
            }
            $stm->bindValue(":id", $id);
            return $stm->execute();
        } catch (PDOException $e) {
            error_log("Error en {$this->table}" . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stm = $this->connection->prepare($query);
            $stm->bindParam(':id', $id);
            return $stm->execute();
        } catch (PDOException $e) {
            error_log("Error en {$this->table}" . $e->getMessage());
            throw $e;
        }
    }

    public function hasMany(string $relatedTable, string $foreignKey, $parentId): array
    {
        try {
            $sql = "SELECT * FROM {$relatedTable} WHERE {$foreignKey} = :parent_id";
            $stm = $this->connection->prepare($sql);
            $stm->bindValue(':parent_id', $parentId);
            $stm->execute();
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en hasMany {$relatedTable}: " . $e->getMessage());
            throw $e;
        }
    }

    public function belongsTo(string $relatedTable, $foreignKeyValue): ?array
    {
        try {
            $sql = "SELECT * FROM {$relatedTable} WHERE id = :id";
            $stm = $this->connection->prepare($sql);
            $stm->bindValue(':id', $foreignKeyValue);
            $stm->execute();
            return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error en belongsTo {$relatedTable}: " . $e->getMessage());
            throw $e;
        }
    }

    public function belongsToMany(
        string $relatedTable,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        $parentId
    ): array {
        try {
            $sql = "
            SELECT r.*
            FROM {$relatedTable} r
            INNER JOIN {$pivotTable} p ON r.id = p.{$relatedPivotKey}
            WHERE p.{$foreignPivotKey} = :parent_id
        ";
            $stm = $this->connection->prepare($sql);
            $stm->bindValue(':parent_id', $parentId);
            $stm->execute();
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en belongsToMany {$relatedTable}: " . $e->getMessage());
            throw $e;
        }
    }

    private function reset(): void
    {
        $this->wheres = [];
        $this->params = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
    }
}
