<?php
// Файл для выхода из системы
session_start();

// Удаляем все данные сессии
session_unset();
session_destroy();

// Удаляем cookie "запомнить меня", если она существует
if (isset($_COOKIE['bookswap_remember'])) {
    setcookie('bookswap_remember', '', time() - 3600, '/');
}

// Перенаправляем на страницу входа
header('Location: login.php');
exit();
?>
