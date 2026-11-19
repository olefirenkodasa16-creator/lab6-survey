<?php
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Опитування: ШІ у навчанні';
$questions = get_questions();
$errors = [];
$submitted = false;
$submittedAt = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$data, $errors] = validate_survey($_POST);
    $old = $_POST;

    if (!$errors) {
        $submittedAt = date('d.m.Y H:i:s');
        try {
            save_to_json($data, $submittedAt);   // файл у папці survey
            save_to_db($data, $submittedAt);     // запис у базу даних
            $submitted = true;
        } catch (Throwable $ex) {
            $errors[] = 'Не вдалося зберегти відповідь. Спробуйте пізніше.';
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($submitted): ?>
    <div class="card success">
        <h2>Дякуємо за участь!</h2>
        <p>Вашу відповідь збережено.</p>
        <p><strong>Дата і час заповнення форми:</strong> <?= e($submittedAt) ?></p>
        <p><a href="survey.php">Заповнити ще раз</a></p>
    </div>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="card error">
            <strong>Виправте помилки:</strong>
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="survey.php" class="card">
        <label>Ім'я
            <input type="text" name="name" maxlength="100" required value="<?= e($old['name'] ?? '') ?>">
        </label>

        <label>Email
            <input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>">
        </label>

        <?php $n = 1; foreach ($questions as $key => $q): ?>
            <fieldset>
                <legend><?= $n++ ?>. <?= e($q['text']) ?></legend>

                <?php if ($q['type'] === 'radio'): ?>
                    <?php foreach ($q['options'] as $opt): ?>
                        <label class="opt">
                            <input type="radio" name="<?= $key ?>" value="<?= e($opt) ?>"
                                <?= (($old[$key] ?? '') === $opt) ? 'checked' : '' ?>>
                            <?= e($opt) ?>
                        </label>
                    <?php endforeach; ?>

                <?php elseif ($q['type'] === 'checkbox'): ?>
                    <?php foreach ($q['options'] as $opt): ?>
                        <label class="opt">
                            <input type="checkbox" name="<?= $key ?>[]" value="<?= e($opt) ?>"
                                <?= in_array($opt, (array)($old[$key] ?? []), true) ? 'checked' : '' ?>>
                            <?= e($opt) ?>
                        </label>
                    <?php endforeach; ?>

                <?php else: ?>
                    <textarea name="<?= $key ?>" rows="4" maxlength="1000"><?= e($old[$key] ?? '') ?></textarea>
                <?php endif; ?>
            </fieldset>
        <?php endforeach; ?>

        <button type="submit">Надіслати</button>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
