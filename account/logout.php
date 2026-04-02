<?php
require_once __DIR__ . '/../includes/init.php';

logoutCurrentUser();
redirect('account/login.php');
