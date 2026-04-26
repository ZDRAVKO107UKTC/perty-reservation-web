<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

logout_user();
set_flash('info', 'Администраторската сесия беше затворена.');
redirect('logIn.php');
