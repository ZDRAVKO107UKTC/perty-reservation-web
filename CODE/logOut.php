<?php
require_once __DIR__ . '/includes/bootstrap.php';

logout_user();
set_flash('info', 'Излязохте успешно от профила.');
redirect('index.php');
