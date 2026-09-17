<?php
require_once __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

// JSON-відповідь із потрібним кодом статусу і завершити
function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function respondSuccess(array $data, int $statusCode = 200): void
{
    respond($statusCode, ['success' => true, 'data' => $data]);
}

function respondError(string $message, int $statusCode): void
{
    respond($statusCode, ['success' => false, 'error' => $message]);
}

// прочитати тіло запиту, спершу $_POST, інакше JSON-тіло 
function getRequestBody(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function listTransactions(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT * FROM transactions ORDER BY transaction_date DESC, id DESC');
    respondSuccess($stmt->fetchAll());
}

function getTransaction(PDO $pdo, int $id): void
{
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        respondError("Операцію з id={$id} не знайдено", 404);
    }

    respondSuccess($row);
}

function createTransaction(PDO $pdo): void
{
    $body = getRequestBody();

    $amount = $body['amount'] ?? null;
    $category = trim((string) ($body['category'] ?? ''));
    $date = trim((string) ($body['transaction_date'] ?? ''));

    $missing = [];
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        $missing[] = 'amount (число)';
    }
    if ($category === '') {
        $missing[] = 'category (рядок)';
    }
    if ($date === '' || !DateTime::createFromFormat('Y-m-d', $date)) {
        $missing[] = 'transaction_date (формат РРРР-ММ-ДД)';
    }

    if (!empty($missing)) {
        respondError('Некоректні або відсутні поля: ' . implode(', ', $missing), 400);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO transactions (amount, category, transaction_date) VALUES (:amount, :category, :date)'
    );
    $stmt->execute([
        ':amount'   => (float) $amount,
        ':category' => $category,
        ':date'     => $date,
    ]);

    $newId = (int) $pdo->lastInsertId();
    $row = $pdo->prepare('SELECT * FROM transactions WHERE id = :id');
    $row->execute([':id' => $newId]);

    respondSuccess($row->fetch(), 201);
}

// доменна дія: поточний баланс 
function getBalanceAction(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT SUM(amount) AS balance FROM transactions');
    $balance = (float) ($stmt->fetch()['balance'] ?? 0);

    respondSuccess(['balance' => $balance]);
}

// маршрутизація
$method = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? null;
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

if ($resource !== 'transactions') {
    respondError('Невідомий ресурс. Підтримується лише resource=transactions', 404);
}

if ($method === 'GET') {
    if ($id === null) {
        listTransactions($pdo);
    } else {
        if (!ctype_digit((string) $id)) {
            respondError('Параметр id має бути цілим додатним числом', 400);
        }
        getTransaction($pdo, (int) $id);
    }
} elseif ($method === 'POST') {
    if ($action === 'balance') {
        getBalanceAction($pdo);
    } elseif ($action === null) {
        createTransaction($pdo);
    } else {
        respondError("Невідома дія action={$action}", 400);
    }
} else {
    respondError("Метод {$method} не підтримується для resource=transactions", 405);
}
