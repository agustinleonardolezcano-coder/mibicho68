<?php
ob_start();
require_once __DIR__ . '/auth.php';
logoutUser();
redirect('/login.php');
