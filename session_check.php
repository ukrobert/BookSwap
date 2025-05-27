<?php
// Начинаем сессию
session_start();

// Функция для проверки, авторизован ли пользователь
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['is_logged_in'] === true;
}

// Функция для перенаправления неавторизованных пользователей
function redirectIfNotLoggedIn($redirect_to = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_to");
        exit();
    }
}

// Функция для перенаправления авторизованных пользователей
function redirectIfLoggedIn($redirect_to = 'profile.php') {
    if (isLoggedIn()) {
        header("Location: $redirect_to");
        exit();
    }
}
?>
