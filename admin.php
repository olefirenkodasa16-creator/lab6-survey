<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Адмін-панель';
$loginError = '';

// Вихід
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Вхід
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    if (hash_equals(ADMIN_LOGIN, $login) && hash_equals(ADMIN_PASSWORD, $password)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;       // фіксуємо вхід у $_SESSION
        $_SESSION['login_time'] = date('d.m.Y H:i:s');
        header('Location: admin.php');
        exit;
    }
    $loginError = 'Невірний логін або пароль.';
}

// Видалення відповіді (тільки для адміна, тільки POST + CSRF)
if (is_admin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        delete_response((int)$_POST['delete_id']);
    }
    header('Location: admin.php');
    exit;
}

include __DIR__ . '/includes/header.php';

if (!is_admin()): ?>
    <form method="post" action="admin.php" class="card narrow">
        <h2>Вхід для адміністратора</h2>
        <?php if ($loginError): ?>
            <p class="error-text"><?= e($loginError) ?></p>
        <?php endif; ?>
        <label>Логін
            <input type="text" name="login" required>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button type="submit">Увійти</button>
    </form>
<?php else:
    $rows = get_all_responses();
    $questions = get_questions();
    ?>
    <div class="toolbar">
        <span>Вхід виконано: <?= e($_SESSION['login_time'] ?? '') ?></span>
        <span>
            <a class="btn" href="export.php?format=csv">Експорт CSV</a>
            <a class="btn" href="export.php?format=json">Експорт JSON</a>
            <a class="btn secondary" href="admin.php?logout=1">Вийти</a>
        </span>
    </div>

    <p>Всього відповідей: <strong><?= count($rows) ?></strong></p>

    <?php if (!$rows): ?>
        <div class="card">Відповідей поки немає.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Ім'я</th>
                    <th>Email</th>
                    <?php foreach ($questions as $q): ?>
                        <th><?= e($q['text']) ?></th>
                    <?php endforeach; ?>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= e($r['created_at']) ?></td>
                        <td><?= e($r['name']) ?></td>
                        <td><?= e($r['email']) ?></td>
                        <td><?= e($r['q1']) ?></td>
                        <td><?= e($r['q2']) ?></td>
                        <td><?= e($r['q3']) ?></td>
                        <td><?= nl2br(e($r['q4'])) ?></td>
                        <td>
                            <form method="post" action="admin.php"
                                  onsubmit="return confirm('Видалити цю відповідь?');">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="delete_id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" class="danger">Видалити</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif;

include __DIR__ . '/includes/footer.php';
