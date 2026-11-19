<?php
// Налаштування сайту
date_default_timezone_set('Europe/Kyiv');

define('DB_PATH', __DIR__ . '/../data/survey.sqlite'); // SQLite - справжня SQL-база
define('SURVEY_DIR', __DIR__ . '/../survey');          // папка для .json файлів

// Логін і пароль адміністратора (ЗМІНИ!)
define('ADMIN_LOGIN', 'admin');
define('ADMIN_PASSWORD', 'admin123');
