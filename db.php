<?php
$databaseUrl = getenv('DATABASE_URL') ?: getenv('POSTGRES_URL') ?: getenv('POSTGRES_PUBLIC_URL') ?: '';

$host = getenv('DB_HOST') ?: getenv('PGHOST') ?: 'db';
$db   = getenv('DB_NAME') ?: getenv('PGDATABASE') ?: 'estudantes_db';
$user = getenv('DB_USER') ?: getenv('PGUSER') ?: 'postgres';
$pass = getenv('DB_PASSWORD') ?: getenv('PGPASSWORD') ?: 'postgres';
$port = getenv('DB_PORT') ?: getenv('PGPORT') ?: '5432';
$sslmode = getenv('DB_SSLMODE') ?: getenv('PGSSLMODE') ?: '';

if ($databaseUrl) {
    $url = parse_url($databaseUrl);

    if ($url !== false) {
        $host = $url['host'] ?? $host;
        $db = isset($url['path']) ? ltrim($url['path'], '/') : $db;
        $user = isset($url['user']) ? urldecode($url['user']) : $user;
        $pass = isset($url['pass']) ? urldecode($url['pass']) : $pass;
        $port = $url['port'] ?? $port;

        if (isset($url['query'])) {
            parse_str($url['query'], $query);
            $sslmode = $query['sslmode'] ?? $sslmode;
        }
    }
}

try {
    if (str_ends_with($host, '.internal')) {
        throw new PDOException("Host privado '$host' nao e acessivel fora da rede interna do provedor. Use o host publico do banco de dados.");
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    if ($sslmode) {
        $dsn .= ";sslmode=$sslmode";
    }

    $conn = new PDO(
        $dsn,
        $user,
        $pass
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("
        CREATE TABLE IF NOT EXISTS estudantes (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            numero VARCHAR(50) NOT NULL,
            curso VARCHAR(100) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $conn->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id SERIAL PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(120) NOT NULL UNIQUE,
            senha VARCHAR(120) NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute(["admin@escola.co.mz"]);

    if (!$stmt->fetch()) {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
        $stmt->execute(["Administrador", "admin@escola.co.mz", "admin123"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(["erro" => "Falha na BD: " . $e->getMessage()]));
}
?>
