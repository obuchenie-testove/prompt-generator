<?php
require_once __DIR__ . '/../auth.php';
logout_user();
set_flash('info', 'Излязохте от системата.');
redirect('/admin/login.php');
