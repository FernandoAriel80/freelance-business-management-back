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
    //private ?array $currentRow = null;

    public function __construct(
        private string $table,
        private PDO $connection
    ) {
        $this->table = $table;
        $this->connection = $connection;
    }

    /**
     * Summary of all
     * @param array $params
     * @return array
     */
    public function all(array $params)
    {
        $values = $this->buildSelect($params);
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

    /**
     * Summary of where
     * @param string $column
     * @param string $comparison
     * @param mixed $value
     * @param string $boolean
     * @throws InvalidArgumentException
     * @return ORM
     */
    public function where(string $column, string $comparison, $value, string $boolean = 'AND'): self
    {
        $allowed = ['=', '!=', '<', '>', '<=', '>=', 'LIKE'];
        if (!in_array($comparison, $allowed, true)) {
            throw new InvalidArgumentException("Operador no permitido");
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException("Columna inválida");
        }

        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Booleano inválido: {$boolean}");
        }

        $param = ":w" . count($this->params);
        $this->wheres[] = [
            "type" => "basic",
            "sql" => "{$column} {$comparison} {$param}",
            "bool" => $boolean,
        ];
        $this->params[$param] = $value;

        return $this;
    }

    /**
     * Summary of orWhere
     * @param string $column
     * @param string $comparison
     * @param mixed $value
     * @return ORM
     */
    public function orWhere(string $column, string $comparison, $value): self
    {
        return $this->where($column, $comparison, $value, 'OR');
    }

    /**
     *   ->whereIn('id', $ids)
     *   ->get();
     */
    /**
     * Summary of whereIn
     * @param string $column
     * @param array $values
     * @throws InvalidArgumentException
     * @return ORM
     */
    public function whereIn(string $column, array $values): self
    {
        // 1) Validar columna (mismo patrón que where)
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $column)) {
            throw new InvalidArgumentException("Columna inválida: {$column}");
        }

        // 2) Si no hay valores, fuerza resultado vacío (IN () es inválido en SQL)
        if (empty($values)) {
            $this->wheres[] = [
                'type' => 'basic',
                'sql' => '1 = 0',
                'bool' => 'AND',
            ];
            return $this;
        }

        // 3) Crear un placeholder por cada valor
        $placeholders = [];
        foreach (array_values($values) as $i => $value) {
            $param = ':wi' . count($this->params);   // usa el contador global → no colisiona
            $placeholders[] = $param;
            $this->params[$param] = $value;
        }

        // 4) Guardar la condición
        $this->wheres[] = [
            'type' => 'basic',
            'sql' => "{$column} IN (" . implode(', ', $placeholders) . ")",
            'bool' => 'AND',
        ];

        return $this;
    }

    /**
     * Agrupa condiciones en un bloque entre paréntesis.
     *
     * Uso:
     *   $qb->whereGroup(function ($q) use ($like) {
     *       $q->where('name', 'LIKE', $like)
     *         ->orWhere('email', 'LIKE', $like);
     *   });
     *
     * Genera: WHERE (name LIKE ? OR email LIKE ?) AND ...
     */
    /**
     * Summary of whereGroup
     * @param callable $callback
     * @param string $boolean
     * @throws InvalidArgumentException
     * @return ORM
     */
    public function whereGroup(callable $callback, string $boolean = 'AND'): self
    {
        $boolean = strtoupper($boolean);
        if (!in_array($boolean, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException("Booleano inválido: {$boolean}");
        }

        // Guardamos el estado previo y aislamos el grupo
        $prevWheres = $this->wheres;
        $this->wheres = [];

        // El callback agrega condiciones al grupo (usa $this->params globales)
        $callback($this);

        // Construimos el SQL interno del grupo SIN el " WHERE (...)"
        $innerParts = [];
        foreach ($this->wheres as $i => $w) {
            $bool = $i === 0 ? '' : ' ' . $w['bool'] . ' ';
            $innerParts[] = $bool . $w['sql'];
        }
        $inner = implode('', $innerParts);

        // Restauramos el estado previo y agregamos el grupo como un bloque
        $this->wheres = $prevWheres;
        $this->wheres[] = [
            "type" => "group",
            "sql" => $inner,
            "bool" => $boolean,
        ];

        return $this;
    }

    /**
     * Summary of buildWhere
     * @return string
     */
    private function buildWhere(): string
    {
        if (!$this->wheres) {
            return '';
        }

        // Separamos básicos de grupos
        $basics = [];
        $groups = [];
        foreach ($this->wheres as $w) {
            if (($w['type'] ?? 'basic') === 'group') {
                $groups[] = $w;
            } else {
                $basics[] = $w;
            }
        }

        $blocks = [];

        // Bloque de básicos: todos juntos dentro de un paréntesis
        // (mantiene el comportamiento anterior para where/orWhere sueltos)
        if ($basics) {
            $inner = '';
            foreach ($basics as $i => $w) {
                $bool = $i === 0 ? '' : ' ' . $w['bool'] . ' ';
                $inner .= $bool . $w['sql'];
            }
            $blocks[] = '(' . $inner . ')';
        }

        // Bloques de grupo: cada uno ya viene entre paréntesis
        foreach ($groups as $g) {
            $blocks[] = '(' . $g['sql'] . ')';
        }

        return ' WHERE ' . implode(' AND ', $blocks);
    }
    /**
     * Summary of paginate
     * @param int $page
     * @param int $perPage
     * @param array $columns
     * @return array{data: mixed, meta: array{"current_page": int, from: int, "has_next": bool, "has_prev": bool, "last_page": int, "per_page": int, to: int|mixed, total: mixed}}
     */
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

    /**
     * Summary of count
     * @return int
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}" . $this->buildWhere();
        $stm = $this->connection->prepare($sql);
        $stm->execute($this->params);
        return (int) $stm->fetchColumn();
    }
    /**
     * Summary of limit
     * @param int $limit
     * @param int $offset
     * @return ORM
     */
    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Summary of get
     * @param array $params
     * @return array
     */
    public function get(array $params = ["*"]): array
    {
        $values = $values = $this->buildSelect($params);
        try {
            $sql = "SELECT {$values} FROM {$this->table}";

            $sql .= $this->buildWhere();

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

    /**
     * Summary of first
     * @param array $params
     */
    public function first(array $params = ["*"]): ?array
    {
        $results = $this->get($params);
        return $results[0] ?? null;
    }

    /**
     * Summary of orderBy
     * @param string $column
     * @param string $direction
     * @throws InvalidArgumentException
     * @return ORM
     */
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

    /**
     * Summary of find
     * @param mixed $id
     * @param array $params
     * @return mixed|null
     */
    public function find($id, array $params = ["*"])
    {
        $values = $values = $this->buildSelect($params);
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

    /**
     * Summary of load
     * @param mixed $id
     * @return ORM|null
     */
    /*  public function load($id): ?self
     {
         $row = $this->find($id);
         if (!$row)
             return null;

         $this->currentRow = $row;
         return $this;
     } */

    /* public function getId()
    {
        return $this->currentRow['id'] ?? null;
    } */

    /**
     * Summary of getAttribute
     * @param string $key
     */
    /* public function getAttribute(string $key)
    {
        return $this->currentRow[$key] ?? null;
    }

    public function toArray(): array
    {
        return $this->currentRow;
    } */

    /**
     * Summary of create
     * @param array $data
     * @return bool
     */
    public function create(array $data)
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

    /**
     * Summary of update
     * @param mixed $id
     * @param array $data
     * @return bool
     */
    public function update($id, array $data)
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

    /**
     * Summary of delete
     * @param mixed $id
     * @return bool
     */
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

    /**
     * $customer['appointments'] = $this->customer->hasMany('appointments', 'customer_id', 'c1'); 
     *
     * Summary of hasMany
     * @param string $relatedTable
     * @param string $foreignKey
     * @param mixed $parentId
     * @return array
     */
    public function hasMany(string $relatedTable, string $foreignKey, $parentId, array $params = ["*"]): array
    {
        $values = $values = $this->buildSelect($params);
        try {
            $sql = "SELECT {$values} FROM {$relatedTable} WHERE {$foreignKey} = :parent_id";
            $stm = $this->connection->prepare($sql);
            $stm->bindValue(':parent_id', $parentId);
            $stm->execute();
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en hasMany {$relatedTable}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     *  $this->appointment->belongsTo('customers', $appointment['customer_id']),
     *
     * Summary of belongsTo
     * @param string $relatedTable
     * @param mixed $foreignKeyValue
     * @return mixed|null
     */
    public function belongsTo(string $relatedTable, $foreignKeyValue, array $params = ["*"]): ?array
    {
        $values = $this->buildSelect($params);
        try {
            $sql = "SELECT {$values} FROM {$relatedTable} WHERE id = :id";
            $stm = $this->connection->prepare($sql);
            $stm->bindValue(':id', $foreignKeyValue);
            $stm->execute();
            return $stm->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error en belongsTo {$relatedTable}: " . $e->getMessage());
            throw $e;
        }
    }
    /**
     * $appointment['services'] = $this->appointment->belongsToMany(
     *'services',            // relatedTable
     *'appointment_service', // pivotTable
     *'appointment_id',      // foreignPivotKey (FK del padre en la pivote)
     *'service_id',          // relatedPivotKey (FK del relacionado en la pivote)
     *'a1'                   // parentId
     *);  
     *
     * Summary of belongsToMany
     * @param string $relatedTable
     * @param string $pivotTable
     * @param string $foreignPivotKey
     * @param string $relatedPivotKey
     * @param mixed $parentId
     * @param array $params
     * @throws InvalidArgumentException
     * @return array
     */
    public function belongsToMany(
        string $relatedTable,
        string $pivotTable,
        string $foreignPivotKey,
        string $relatedPivotKey,
        $parentId,
        array $params = ["*"]
    ): array {
        // Validar identificadores
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $relatedTable)) {
            throw new InvalidArgumentException("Tabla inválida: {$relatedTable}");
        }
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $pivotTable)) {
            throw new InvalidArgumentException("Tabla pivote inválida: {$pivotTable}");
        }
        foreach ([$foreignPivotKey, $relatedPivotKey] as $col) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $col)) {
                throw new InvalidArgumentException("Columna inválida: {$col}");
            }
        }

        // Armar SELECT con prefijo r.
        if (empty($params) || $params === ['*']) {
            $select = 'r.*';
        } else {
            $clean = [];
            foreach ($params as $col) {
                $col = trim($col);
                // Permitir "col" o "col AS alias". NO permitir "tabla.col" porque ya forzamos r.
                if (
                    !preg_match(
                        '/^[a-zA-Z_][a-zA-Z0-9_]*(\s+AS\s+[a-zA-Z_][a-zA-Z0-9_]*)?$/i',
                        $col
                    )
                ) {
                    throw new InvalidArgumentException("Columna inválida en SELECT: {$col}");
                }
                $clean[] = 'r.' . $col;
            }
            $select = implode(', ', $clean);
        }

        try {
            $sql = "
            SELECT {$select}
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

    /**
     * Carga una relación hasMany para un conjunto de filas.
     * Agrupa por FK del hijo y hace UNA sola query.
     * 
     * 
     *  $rows = $this->customer->attachHasMany(
     *  [$customer],          // ← array con 1 elemento
     *  'appointments',       // tabla hija
     *  'customer_id',        // FK en la hija
     *  'appointments'        // clave donde anidar
     *  );
     *  return $rows[0];
     *
     * Summary of attachHasMany
     * @param array $rows
     * @param string $relatedTable
     * @param string $foreignKeyCol
     * @param string $as
     * @param string $localKey
     * @param array $params
     * @return array
     */
    public function attachHasMany(
        array $rows,
        string $relatedTable,
        string $foreignKeyCol,
        string $as,
        string $localKey = 'id',
        array $params = ["*"]
    ): array {
        if (empty($rows)) {
            return $rows;
        }

        $ids = array_values(array_unique(array_column($rows, $localKey)));
        if (empty($ids)) {
            foreach ($rows as &$row) {
                $row[$as] = [];
            }
            return $rows;
        }

        $placeholders = [];
        $paramsSql = [];
        foreach ($ids as $i => $id) {
            $p = ":ah{$i}";
            $placeholders[] = $p;
            $paramsSql[$p] = $id;
        }
        $values = $this->buildSelect($params);
        $sql = "SELECT {$values} FROM {$relatedTable} WHERE {$foreignKeyCol} IN (" . implode(', ', $placeholders) . ")";
        $stm = $this->connection->prepare($sql);
        $stm->execute($paramsSql);

        // Agrupar hijos por FK
        $grouped = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $related) {
            $grouped[$related[$foreignKeyCol]][] = $related;
        }

        foreach ($rows as &$row) {
            $row[$as] = $grouped[$row[$localKey]] ?? [];
        }

        return $rows;
    }

    /**
     * Carga una relación belongsTo para un conjunto de filas.
     * Agrupa por FK y hace UNA sola query (IN ...).
     * 
     * $this->appointment->attachBelongsTo(
     * $appointments,
     * 'customers',      // tabla relacionada
     * 'customer_id',    // FK en appointments
     * 'customer'        // clave donde anidar
     * );

     * Summary of attachBelongsTo
     * @param array $rows
     * @param string $relatedTable
     * @param string $foreignKeyCol
     * @param string $as
     * @param string $ownerKey
     * @param array $params
     * @return array
     */
    public function attachBelongsTo(
        array $rows,
        string $relatedTable,
        string $foreignKeyCol,
        string $as,
        string $ownerKey = 'id',
        array $params = ['*']
    ): array {
        if (empty($rows))
            return $rows;

        $ids = array_values(array_unique(array_filter(array_column($rows, $foreignKeyCol))));
        if (empty($ids)) {
            foreach ($rows as &$row)
                $row[$as] = null;
            return $rows;
        }

        $values = $this->buildSelect($params);

        $placeholders = [];
        $paramsSql = [];
        foreach ($ids as $i => $id) {
            $p = ":ab{$i}";
            $placeholders[] = $p;
            $paramsSql[$p] = $id;
        }

        $sql = "SELECT {$values} FROM {$relatedTable} WHERE {$ownerKey} IN (" . implode(', ', $placeholders) . ")";
        $stm = $this->connection->prepare($sql);
        $stm->execute($paramsSql);

        $byId = [];
        foreach ($stm->fetchAll(PDO::FETCH_ASSOC) as $related) {
            $byId[$related[$ownerKey]] = $related;
        }

        foreach ($rows as &$row) {
            $row[$as] = $byId[$row[$foreignKeyCol]] ?? null;
        }

        return $rows;
    }

    /**
     * Convierte ['id', 'name', 'company AS c'] en una lista segura para SELECT.
     * Si viene vacío o ['*'], devuelve '*'.
     */
    /**
     * Summary of buildSelect
     * @param array $params
     * @throws InvalidArgumentException
     * @return string
     */
    private function buildSelect(array $params): string
    {
        if (empty($params) || $params === ['*']) {
            return '*';
        }

        $clean = [];
        foreach ($params as $col) {
            // Permite: columna, tabla.columna, y opcional "AS alias"
            if (
                !preg_match(
                    '/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?(\s+AS\s+[a-zA-Z_][a-zA-Z0-9_]*)?$/i',
                    trim($col)
                )
            ) {
                throw new InvalidArgumentException("Columna inválida en SELECT: {$col}");
            }
            $clean[] = trim($col);
        }

        return implode(', ', $clean);
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