<?php
require_once __DIR__ . '/config.php';

/** Підключення до бази даних SQLite (створює таблицю, якщо її немає) */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('CREATE TABLE IF NOT EXISTS responses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            q1 TEXT NOT NULL,
            q2 TEXT NOT NULL,
            q3 TEXT NOT NULL,
            q4 TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');
    }
    return $pdo;
}

/** Питання анкети */
function get_questions(): array
{
    return [
        'q1' => [
            'text' => 'Як часто ви користуєтесь ШІ-чат-ботами для навчання?',
            'type' => 'radio',
            'options' => ['Щодня', 'Кілька разів на тиждень', 'Рідко', 'Ніколи'],
        ],
        'q2' => [
            'text' => 'Для чого саме ви використовуєте ШІ? (можна обрати кілька)',
            'type' => 'checkbox',
            'options' => ['Написання коду', 'Пояснення тем', 'Переклад', 'Написання текстів', 'Інше'],
        ],
        'q3' => [
            'text' => 'Наскільки ви довіряєте відповідям ШІ?',
            'type' => 'radio',
            'options' => ['Повністю', 'Здебільшого', 'Частково', 'Не довіряю'],
        ],
        'q4' => [
            'text' => 'Ваші побажання або думки щодо використання ШІ в навчанні:',
            'type' => 'text',
        ],
    ];
}

/** Екранування для безпечного виводу в HTML */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Перевірка даних форми. Повертає [дані, помилки] */
function validate_survey(array $post): array
{
    $errors = [];
    $questions = get_questions();

    $name = trim($post['name'] ?? '');
    $email = trim($post['email'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = "Вкажіть ім'я (до 100 символів).";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Вкажіть коректний email.';
    }

    $answers = [];
    foreach ($questions as $key => $q) {
        $value = $post[$key] ?? null;

        if ($q['type'] === 'radio') {
            if (!is_string($value) || !in_array($value, $q['options'], true)) {
                $errors[] = 'Оберіть відповідь на питання: ' . $q['text'];
                continue;
            }
            $answers[$key] = $value;
        } elseif ($q['type'] === 'checkbox') {
            $value = is_array($value) ? $value : [];
            $value = array_values(array_intersect($value, $q['options']));
            if (!$value) {
                $errors[] = 'Оберіть хоча б одну відповідь на питання: ' . $q['text'];
                continue;
            }
            $answers[$key] = implode(', ', $value);
        } else { // text
            $text = trim((string)$value);
            if ($text === '' || mb_strlen($text) > 1000) {
                $errors[] = 'Напишіть відповідь (до 1000 символів) на питання: ' . $q['text'];
                continue;
            }
            $answers[$key] = $text;
        }
    }

    $data = ['name' => $name, 'email' => $email] + $answers;
    return [$data, $errors];
}

/** Збереження відповіді у файл survey/survey_YYYY-MM-DD_HH-MM-SS.json */
function save_to_json(array $data, string $timestamp): string
{
    if (!is_dir(SURVEY_DIR)) {
        mkdir(SURVEY_DIR, 0755, true);
    }
    $filename = 'survey_' . date('Y-m-d_H-i-s') . '_' . substr(bin2hex(random_bytes(2)), 0, 4) . '.json';
    $payload = ['submitted_at' => $timestamp] + $data;
    file_put_contents(
        SURVEY_DIR . '/' . $filename,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
    return $filename;
}

/** Збереження відповіді в базу даних */
function save_to_db(array $data, string $timestamp): int
{
    $stmt = db()->prepare(
        'INSERT INTO responses (name, email, q1, q2, q3, q4, created_at)
         VALUES (:name, :email, :q1, :q2, :q3, :q4, :created_at)'
    );
    $stmt->execute([
        ':name' => $data['name'],
        ':email' => $data['email'],
        ':q1' => $data['q1'],
        ':q2' => $data['q2'],
        ':q3' => $data['q3'],
        ':q4' => $data['q4'],
        ':created_at' => $timestamp,
    ]);
    return (int)db()->lastInsertId();
}

/** Усі відповіді (для адмінки) */
function get_all_responses(): array
{
    return db()->query('SELECT * FROM responses ORDER BY id DESC')->fetchAll();
}

/** Видалення відповіді за id */
function delete_response(int $id): void
{
    $stmt = db()->prepare('DELETE FROM responses WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

/** Чи авторизований адміністратор */
function is_admin(): bool
{
    return !empty($_SESSION['is_admin']);
}

/** CSRF-токен для форм видалення */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
