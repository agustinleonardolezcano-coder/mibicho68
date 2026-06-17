<?php
/**
 * Cooperativa FLB — Conexión a base de datos
 */
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $opts = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        } catch (PDOException $e) {
            // Mostrar error amigable sin revelar credenciales
            error_log('DB Connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Error</title>';
            echo '<style>body{font-family:Arial,sans-serif;background:#07050F;color:#EDE9F6;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:1rem}</style>';
            echo '</head><body><div style="text-align:center"><h2 style="color:#EF4444">⚠ Error de conexión</h2>';
            echo '<p>No se pudo conectar a la base de datos. Por favor intentá más tarde.</p>';
            echo '<p style="font-size:.8rem;color:#7B6A9B">Código: DB_001</p></div></body></html>';
            exit;
        }
    }
    return $pdo;
}

function dbQuery(string $sql, array $params = []): PDOStatement {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetch(string $sql, array $params = []): ?array {
    $r = dbQuery($sql, $params)->fetch();
    return $r ?: null;
}

function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

function dbInsert(string $table, array $data): string {
    $cols = implode(',', array_map(fn($k) => "`$k`", array_keys($data)));
    $phs  = implode(',', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO `$table` ($cols) VALUES ($phs)", array_values($data));
    return getDB()->lastInsertId();
}

function dbUpdate(string $table, array $data, string $where, array $whereParams = []): int {
    $set  = implode(',', array_map(fn($k) => "`$k`=?", array_keys($data)));
    $stmt = dbQuery("UPDATE `$table` SET $set WHERE $where",
        array_merge(array_values($data), $whereParams));
    return $stmt->rowCount();
}
