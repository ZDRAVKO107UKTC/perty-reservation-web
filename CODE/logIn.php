<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(current_user_role() === 'admin' ? 'admin/dashboard.php' : 'index.php');
}

$error = '';
$email = '';
$flash = pull_flash();

if (is_post()) {
    require_valid_csrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Моля, попълнете имейл и парола.';
    } else {
        $statement = db()->prepare(
            'SELECT id_person, first_name_person, last_name_person, email_person, phone_number_person, pass_person, role
             FROM registered_person
             WHERE email_person = ?
             LIMIT 1'
        );
        $statement->execute([$email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['pass_person'])) {
            $error = 'Невалиден имейл или парола.';
        } else {
            if (password_needs_rehash($user['pass_person'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $update = db()->prepare('UPDATE registered_person SET pass_person = ? WHERE id_person = ?');
                $update->execute([$newHash, $user['id_person']]);
                $user['pass_person'] = $newHash;
            }

            login_user($user);
            set_flash('success', 'Успешен вход в профила.');
            redirect(current_user_role() === 'admin' ? 'admin/dashboard.php' : 'index.php');
        }
    }
}
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MyClub | Вход</title>
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
        <span class="eyebrow"><i class="fa-solid fa-right-to-bracket"></i> Вход</span>
        <h1 class="auth-title mt-3 mb-3">Влезте, за да управлявате резервациите си.</h1>
        <p class="section-text mb-4">
          След вход ще виждате активните си маси, ще можете да отменяте резервации и да избирате нови събития без повторно попълване.
        </p>
        <div class="account-meta">
          <div class="meta-block">
            <strong>Какво ще откриете след вход</strong>
            <p class="muted-text mb-0">Вашият профил, историята на резервациите и бърз достъп до следващите клубни вечери.</p>
          </div>
          <div class="meta-block">
            <strong>Нямате акаунт?</strong>
            <p class="muted-text mb-0">Регистрацията отнема по-малко от минута и е нужна само веднъж.</p>
          </div>
        </div>
      </section>

      <section class="auth-card">
        <h2 class="auth-title h3 mb-4">Достъп до профила</h2>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e(flash_type_class($flash['type'])) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" class="brand-form" novalidate>
          <?= csrf_input() ?>
          <div class="mb-3">
            <label for="email" class="form-label">Имейл</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label">Парола</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-brand btn-lg">Влез</button>
            <a href="signUp.php" class="btn btn-ghost-light btn-lg">Нова регистрация</a>
            <a href="index.php" class="btn btn-ghost-light btn-lg">Назад към сайта</a>
          </div>
        </form>
      </section>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
