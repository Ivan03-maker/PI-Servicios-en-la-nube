<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

define('SECRET_TOKEN', 'IvanTokenSeguro2026_jQuery');

$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (strpos($authHeader, 'Bearer ') === 0) {
    $token = substr($authHeader, 7);
} else {
    $token = isset($_GET['token']) ? $_GET['token'] : '';
}

if (isset($_GET['action']) && $_GET['action'] == 'login') {
    echo json_encode(["status" => "success", "token" => SECRET_TOKEN]);
    exit;
}

if ($token !== SECRET_TOKEN) {
    http_response_code(401);
    echo json_encode(["error" => "No autorizado. Token inválido o ausente."]);
    exit;
}

// CONFIGURACIÓN AUTOMÁTICA ADAPTATIVA PARA RAILWAY
$host = getenv('MYSQLHOST') ?: "localhost";
$db   = getenv('MYSQLDATABASE') ?: "db_catalogo";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$port = getenv('MYSQLPORT') ?: "3306";

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión en Railway: " . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
        $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
        $length = isset($_GET['length']) ? intval($_GET['length']) : 3;
        $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
        
        $columns = ['id', 'titulo', 'director', 'anio'];
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
        $orderDir = isset($_GET['order'][0]['dir']) && $_GET['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
        $orderColumn = $columns[$orderColumnIndex];

        // Asegurar que las consultas cuenten correctamente aun estando vacías
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM peliculas")->fetchColumn();
        $totalRecords = $totalRecords ? intval($totalRecords) : 0;

        $queryStr = "SELECT * FROM peliculas WHERE 1=1";
        $params = [];
        if (!empty($searchValue)) {
            $queryStr .= " AND (titulo LIKE :search OR director LIKE :search OR anio LIKE :search)";
            $params[':search'] = "%$searchValue%";
        }

        $stmtFilter = $pdo->prepare($queryStr);
        $stmtFilter->execute($params);
        $totalRecordwithFilter = $stmtFilter->rowCount();

        $queryStr .= " ORDER BY $orderColumn $orderDir LIMIT :start, :length";
        
        $stmt = $pdo->prepare($queryStr);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        
        // Forzar explícitamente enteros para evitar fallos de sintaxis SQL en LIMIT
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$data) {
            $data = []; // Retornar un contenedor vacío limpio si no hay filas
        }

        echo json_encode([
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => intval($totalRecordwithFilter),
            "data" => $data
        ]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['titulo']) || empty($input['director']) || empty($input['anio'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Campos obligatorios vacíos."]);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO peliculas (titulo, director, anio) VALUES (?, ?, ?)");
        $stmt->execute([$input['titulo'], $input['director'], $input['anio']]);
        echo json_encode(["status" => "success", "message" => "Película agregada con éxito"]);
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['id']) || empty($input['titulo']) || empty($input['director']) || empty($input['anio'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE peliculas SET titulo = ?, director = ?, anio = ? WHERE id = ?");
        $stmt->execute([$input['titulo'], $input['director'], $input['anio'], $input['id']]);
        echo json_encode(["status" => "success", "message" => "Película actualizada con éxito"]);
        break;

    case 'DELETE':
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("DELETE FROM peliculas WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            echo json_encode(["status" => "success", "message" => "Película eliminada con éxito"]);
        }
        break;
}
?>