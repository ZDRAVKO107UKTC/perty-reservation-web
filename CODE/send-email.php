<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!is_post()) {
    redirect('index.php#contact');
}

require_valid_csrf();

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = ($_POST['consent'] ?? '') === '1';

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    set_flash('danger', 'Моля, попълнете всички задължителни полета във формата за контакт.');
    redirect('index.php#contact');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Моля, въведете валиден имейл адрес.');
    redirect('index.php#contact');
}

if (!$consent) {
    set_flash('danger', 'Моля, потвърдете съгласието си за обработване на данните за отговор по запитването.');
    redirect('index.php#contact');
}

$insert = db()->prepare(
    'INSERT INTO contact_messages (name, phone, email, subject, message, created_at)
     VALUES (?, ?, ?, ?, ?, NOW())'
);
$insert->execute([$name, $phone, $email, $subject, $message]);

set_flash('success', 'Съобщението беше изпратено успешно. Ще се свържем с вас възможно най-скоро.');
redirect('index.php#contact');
