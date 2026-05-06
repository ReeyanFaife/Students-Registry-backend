<?php
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'db';
$db   = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'estudantes_db';
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: 'root';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';

try {
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $conn->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
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