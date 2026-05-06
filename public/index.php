<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

include __DIR__ . '/../db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method === 'POST' && isset($_GET['acao']) && $_GET['acao'] === 'login') {
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || empty($data->email) || empty($data->senha)) {
        http_response_code(400);
        echo json_encode(["message" => "Email e senha sao obrigatorios"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? AND senha = ? LIMIT 1");
    $stmt->execute([$data->email, $data->senha]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(["message" => "Credenciais invalidas"]);
        exit;
    }

    echo json_encode([
        "message" => "Login efetuado",
        "usuario" => $usuario
    ]);
    exit;
}

if ($method === 'POST' && isset($_GET['acao']) && $_GET['acao'] === 'registro') {
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || empty($data->nome) || empty($data->email) || empty($data->senha)) {
        http_response_code(400);
        echo json_encode(["message" => "Nome, email e senha sao obrigatorios"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$data->email]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["message" => "Ja existe uma conta com este email"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
    $stmt->execute([$data->nome, $data->email, $data->senha]);

    echo json_encode([
        "message" => "Conta criada com sucesso",
        "usuario" => [
            "id" => $conn->lastInsertId(),
            "nome" => $data->nome,
            "email" => $data->email
        ]
    ]);
    exit;
}

switch ($method) {
    case 'GET':
        $stmt = $conn->query("SELECT * FROM estudantes");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        $stmt = $conn->prepare("INSERT INTO estudantes (nome, numero, curso) VALUES (?, ?, ?)");
        $stmt->execute([$data->nome, $data->numero, $data->curso]);
        echo json_encode(["message" => "Estudante criado"]);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        $stmt = $conn->prepare("UPDATE estudantes SET nome=?, numero=?, curso=? WHERE id=?");
        $stmt->execute([$data->nome, $data->numero, $data->curso, $data->id]);
        echo json_encode(["message" => "Atualizado"]);
        break;

    case 'DELETE':
        $id = $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM estudantes WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(["message" => "Removido"]);
        break;
}
