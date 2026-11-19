<?php
// Спільна шапка сторінок. Очікує змінну $pageTitle
?><!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Опитування') ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1><?= e($pageTitle ?? 'Опитування') ?></h1>
    <nav>
        <a href="survey.php">Анкета</a>
        <a href="admin.php">Адмін-панель</a>
    </nav>
</header>
<main>
