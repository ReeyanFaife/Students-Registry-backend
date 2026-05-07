<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . '/db.php';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['acao'] ?? null;

if ($method === 'POST' && $action === 'login') {
    $data = json_decode(file_get_contents('php://input'));

    if (!$data || empty($data->email) || empty($data->senha)) {
        http_response_code(400);
        echo json_encode(['message' => 'Email e senha sao obrigatorios']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id, nome, email FROM usuarios WHERE email = ? AND senha = ? LIMIT 1');
    $stmt->execute([$data->email, $data->senha]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        http_response_code(401);
        echo json_encode(['message' => 'Credenciais invalidas']);
        exit;
    }

    echo json_encode([
        'message' => 'Login efetuado',
        'usuario' => $usuario
    ]);
    exit;
}

if ($method === 'POST' && $action === 'registro') {
    $data = json_decode(file_get_contents('php://input'));

    if (!$data || empty($data->nome) || empty($data->email) || empty($data->senha)) {
        http_response_code(400);
        echo json_encode(['message' => 'Nome, email e senha sao obrigatorios']);
        exit;
    }

    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$data->email]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['message' => 'Ja existe uma conta com este email']);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?) RETURNING id');
    $stmt->execute([$data->nome, $data->email, $data->senha]);
    $usuarioId = $stmt->fetchColumn();

    echo json_encode([
        'message' => 'Conta criada com sucesso',
        'usuario' => [
            'id' => $usuarioId,
            'nome' => $data->nome,
            'email' => $data->email
        ]
    ]);
    exit;
}

if ($method === 'GET' && $action === 'relatorio') {
    $format = strtolower($_GET['formato'] ?? 'json');

    $stmt = $conn->query('SELECT * FROM estudantes');
    $estudantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalEstudantes = count($estudantes);
    $cursos = [];

    foreach ($estudantes as $aluno) {
        $curso = trim($aluno['curso']);
        if ($curso === '') {
            $curso = 'Sem curso definido';
        }
        if (!isset($cursos[$curso])) {
            $cursos[$curso] = 0;
        }
        $cursos[$curso]++;
    }

    $cursosResumo = [];
    foreach ($cursos as $curso => $quantidade) {
        $cursosResumo[] = ['curso' => $curso, 'quantidade' => $quantidade];
    }

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio-estudantes.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['id', 'nome', 'numero', 'curso', 'criado_em']);
        foreach ($estudantes as $aluno) {
            fputcsv($output, [$aluno['id'], $aluno['nome'], $aluno['numero'], $aluno['curso'], $aluno['criado_em']]);
        }
        fclose($output);
        exit;
    }

    echo json_encode([
        'total_estudantes' => $totalEstudantes,
        'total_cursos' => count($cursosResumo),
        'cursos' => $cursosResumo,
        'estudantes' => $estudantes
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($method) {
    case 'GET':
        $stmt = $conn->query('SELECT * FROM estudantes');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        $stmt = $conn->prepare('INSERT INTO estudantes (nome, numero, curso) VALUES (?, ?, ?)');
        $stmt->execute([$data->nome, $data->numero, $data->curso]);
        echo json_encode(['message' => 'Estudante criado']);
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'));
        $stmt = $conn->prepare('UPDATE estudantes SET nome=?, numero=?, curso=? WHERE id=?');
        $stmt->execute([$data->nome, $data->numero, $data->curso, $data->id]);
        echo json_encode(['message' => 'Atualizado']);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['message' => 'ID do estudante e obrigatorio']);
            break;
        }
        $stmt = $conn->prepare('DELETE FROM estudantes WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['message' => 'Removido']);
        break;

    default:
        http_response_code(405);
        echo json_encode(['message' => 'Metodo nao suportado']);
        break;
}
