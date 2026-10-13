<?php require_once __DIR__.'/app.php'; redirect(current_user() ? 'dashboard.php' : 'login.php');
