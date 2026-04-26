<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(current_user_role() === 'admin' ? 'admin/dashboard.php' : 'index.php');
}

$error = '';
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
];

if (is_post()) {
    require_valid_csrf();

    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name'] = trim($_POST['last_name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (
        $formData['first_name'] === '' ||
        $formData['last_name'] === '' ||
        $formData['email'] === '' ||
        $password === '' ||
        $password2 === ''
    ) {
        $error = 'Моля, попълнете всички задължителни полета.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Моля, въведете валиден имейл адрес.';
    } elseif (strlen($password) < 8) {
        $error = 'Паролата трябва да бъде поне 8 символа.';
    } elseif ($password !== $password2) {
        $error = 'Паролите не съвпадат.';
    } else {
        $exists = db()->prepare('SELECT id_person FROM registered_person WHERE email_person = ? LIMIT 1');
        $exists->execute([$formData['email']]);

        if ($exists->fetch()) {
            $error = 'Този имейл вече е регистриран.';
        } else {
            $insert = db()->prepare(
                'INSERT INTO registered_person
                    (first_name_person, last_name_person, email_person, phone_number_person, pass_person, role)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                $formData['first_name'],
                $formData['last_name'],
                $formData['email'],
                $formData['phone'],
                password_hash($password, PASSWORD_DEFAULT),
                'user',
            ]);

            set_flash('success', 'Регистрацията беше успешна. Влезте със своя акаунт.');
            redirect('logIn.php');
        }
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MyClub | Регистрация</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="css/site-refresh.css?v=20260423-status">
</head>
<body class="brand-body auth-page">
  <main class="container">
    <div class="auth-layout">
      <section class="auth-card">
        <span class="eyebrow"><i class="fa-solid fa-user-plus"></i> Регистрация</span>
        <h1 class="auth-title mt-3 mb-3">Създайте профил за по-бързи резервации и ясен контрол върху масите си.</h1>
        <p class="section-text mb-4">
          Профилът ви е мястото, от което потвърждавате маси, следите активните си вечери и отменяте резервации при нужда.
        </p>
        <div class="account-meta">
          <div class="meta-block">
            <strong>Какво получавате</strong>
            <p class="muted-text mb-0">Една история на всички активни резервации и възможност сами да управлявате промените си.</p>
          </div>
          <div class="meta-block">
            <strong>Какво не прави системата</strong>
            <p class="muted-text mb-0">Не прави общи запитвания вместо вас. Резервациите винаги са към конкретно събитие и конкретна маса.</p>
          </div>
        </div>
      </section>

      <section class="auth-card">
        <h2 class="auth-title h3 mb-4">Създай нов акаунт</h2>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="brand-form" novalidate>
          <?= csrf_input() ?>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="first_name" class="form-label">Име</label>
              <input type="text" name="first_name" id="first_name" class="form-control" value="<?= e($formData['first_name']) ?>" autocomplete="given-name" required>
            </div>
            <div class="col-md-6">
              <label for="last_name" class="form-label">Фамилия</label>
              <input type="text" name="last_name" id="last_name" class="form-control" value="<?= e($formData['last_name']) ?>" autocomplete="family-name" required>
            </div>
            <div class="col-12">
              <label for="email" class="form-label">Имейл</label>
              <input type="email" name="email" id="email" class="form-control" value="<?= e($formData['email']) ?>" autocomplete="email" required>
            </div>
            <div class="col-12">
              <label for="phone" class="form-label">Телефон</label>
              <input type="text" name="phone" id="phone" class="form-control" value="<?= e($formData['phone']) ?>" autocomplete="tel">
            </div>
            <div class="col-md-6">
              <label for="password" class="form-label">Парола</label>
              <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" required>
            </div>
            <div class="col-md-6">
              <label for="password2" class="form-label">Повтори паролата</label>
              <input type="password" name="password2" id="password2" class="form-control" autocomplete="new-password" required>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2 mt-2">
              <button type="submit" class="btn btn-brand btn-lg">Създай акаунт</button>
              <a href="logIn.php" class="btn btn-ghost-light btn-lg">Вече имам профил</a>
            </div>
          </div>
        </form>
      </section>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
