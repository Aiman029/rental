<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

logoutUser();
set_flash('info', 'Anda telah berjaya log keluar dari sistem.');
header('Location: ' . BASE_URL . '/public/index.php');
exit;
