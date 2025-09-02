<?php
/**
 * Тестовый файл для проверки логирования Boxberry
 */

// Подключаем WordPress
require_once('/home/irinaf/www/medknigaservis.ru/www/wp-load.php');

// Проверяем, включена ли отладка
echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'ВКЛЮЧЕНА' : 'ОТКЛЮЧЕНА') . "\n";

// Тестируем логирование
if (class_exists('IN_WC_CRM\Plugin')) {
    $plugin = \IN_WC_CRM\Plugin::get();
    $plugin->log('Тестовое сообщение Boxberry', 'boxberry-rest.log');
    echo "Лог записан в: " . $plugin->path . "boxberry-rest.log\n";
} else {
    echo "Класс Plugin не найден\n";
}

// Проверяем, создался ли файл
$logFile = '/home/irinaf/www/medknigaservis.ru/www/wp-content/plugins/in-wc-crm/boxberry-rest.log';
if (file_exists($logFile)) {
    echo "Файл лога существует: " . $logFile . "\n";
    echo "Размер: " . filesize($logFile) . " байт\n";
    echo "Последние 10 строк:\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "Файл лога не найден: " . $logFile . "\n";
}
?>
