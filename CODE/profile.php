<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$connection = db();
$flash = pull_flash();
$userId = current_user_id();

if (is_post() && ($_POST['action'] ?? '') === 'cancel_reservation') {
    require_valid_csrf();

    $reservationId = (int) ($_POST['reservation_id'] ?? 0);
    $delete = $connection->prepare(
        'DELETE r
         FROM reservation r
         JOIN parties p ON p.id_party = r.id_party
         WHERE r.id_reservation = ? AND r.id_person = ? AND p.event_date >= NOW()'
    );
    $delete->execute([$reservationId, $userId]);

    if ($delete->rowCount() > 0) {
        set_flash('success', 'Резервацията беше отменена успешно.');
    } else {
        set_flash('warning', 'Резервацията не може да бъде отменена след началото на събитието или записът не беше намерен.');
    }

    redirect('profile.php');
}

$userStatement = $connection->prepare(
    'SELECT id_person, first_name_person, last_name_person, email_person, phone_number_person, role
     FROM registered_person
     WHERE id_person = ?
     LIMIT 1'
);
$userStatement->execute([$userId]);
$user = $userStatement->fetch();

if (!$user) {
    logout_user();
    set_flash('warning', 'Профилът ви не беше намерен. Моля, влезте отново.');
    redirect('logIn.php');
}

$_SESSION['first_name'] = $user['first_name_person'];
$_SESSION['last_name'] = $user['last_name_person'];
$_SESSION['email'] = $user['email_person'];
$_SESSION['phone_number'] = $user['phone_number_person'];
$_SESSION['role'] = normalize_role($user['role']);

$reservationStatement = $connection->prepare(
    'SELECT r.id_reservation,
            r.date_for_reservation,
            t.table_number,
            t.seats_number,
            p.name_party,
            p.event_date,
            CASE WHEN p.event_date >= NOW() THEN 1 ELSE 0 END AS can_cancel
     FROM reservation r
     JOIN tables t ON r.id_table = t.id_tables
     JOIN parties p ON r.id_party = p.id_party
     WHERE r.id_person = ?
     ORDER BY p.event_date ASC, t.table_number ASC'
);
$reservationStatement->execute([$userId]);
$reservations = $reservationStatement->fetchAll();
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MyClub | Профил</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="css/site-refresh.css?v=20260423-status">
</head>
<body class="brand-body profile-page">
  <main class="container">
    <div class="profile-layout">
      <section class="profile-card">
        <span class="eyebrow"><i class="fa-solid fa-id-card"></i> Моят профил</span>
        <h1 class="profile-title mt-3 mb-3">Профил на <?= e($user['first_name_person'] . ' ' . $user['last_name_person']) ?></h1>
        <p class="section-text mb-4">
          Оттук следите активните си резервации и отменяте маси, ако искате да резервирате нова за същото събитие.
        </p>

        <div class="account-meta">
          <div class="meta-block">
            <strong>Имейл</strong>
            <p class="muted-text mb-0"><?= e($user['email_person']) ?></p>
          </div>
          <div class="meta-block">
            <strong>Телефон</strong>
            <p class="muted-text mb-0"><?= e($user['phone_number_person'] ?: 'Не е добавен') ?></p>
          </div>
          <div class="meta-block">
            <strong>Роля</strong>
            <p class="muted-text mb-0"><?= e($user['role'] === 'admin' ? 'Администратор' : 'Потребител') ?></p>
          </div>
          <div class="meta-block">
            <strong>Полезно уточнение</strong>
            <p class="muted-text mb-0">Системата позволява една активна маса на потребител за конкретно събитие.</p>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="reservation.php" class="btn btn-brand btn-lg">Нова резервация</a>
          <a href="index.php" class="btn btn-ghost-light btn-lg">Към сайта</a>
          <?php if (current_user_role() === 'admin'): ?>
            <a href="admin/dashboard.php" class="btn btn-ghost-light btn-lg">Админ панел</a>
          <?php endif; ?>
          <a href="logOut.php" class="btn btn-ghost-light btn-lg">Изход</a>
        </div>
      </section>

      <section class="profile-card">
        <h2 class="profile-title h3 mb-4">Активни и минали резервации</h2>

        <?php if ($flash): ?>
          <div class="alert alert-<?= e(flash_type_class($flash['type'])) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if ($reservations): ?>
          <div class="profile-table-shell">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Събитие</th>
                    <th>Дата</th>
                    <th>Маса</th>
                    <th>Места</th>
                    <th>Създадена</th>
                    <th>Статус</th>
                    <th class="text-end">Действие</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reservations as $reservation): ?>
                    <?php $canCancel = (bool) $reservation['can_cancel']; ?>
                    <tr>
                      <td data-label="#"><?= (int) $reservation['id_reservation'] ?></td>
                      <td data-label="Събитие"><?= e($reservation['name_party']) ?></td>
                      <td data-label="Дата"><?= e(format_datetime($reservation['event_date'])) ?></td>
                      <td data-label="Маса"><?= (int) $reservation['table_number'] ?></td>
                      <td data-label="Места"><?= (int) $reservation['seats_number'] ?></td>
                      <td data-label="Създадена"><?= e(format_datetime($reservation['date_for_reservation'])) ?></td>
                      <td class="status-cell" data-label="Статус">
                        <span class="status-pill <?= $canCancel ? 'is-success' : 'is-warning' ?>">
                          <?= $canCancel ? 'Активна' : 'Приключило събитие' ?>
                        </span>
                      </td>
                      <td class="text-end" data-label="Действие">
                        <?php if ($canCancel): ?>
                          <form method="post" class="d-inline" onsubmit="return confirm('Сигурни ли сте, че искате да отмените тази резервация?');">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="cancel_reservation">
                            <input type="hidden" name="reservation_id" value="<?= (int) $reservation['id_reservation'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Отмени</button>
                          </form>
                        <?php else: ?>
                          <span class="text-secondary small">Само за преглед</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php else: ?>
          <div class="meta-block">
            <strong>Все още няма направени резервации</strong>
            <p class="muted-text mb-0">Изберете предстоящо събитие и маса от резервационния екран, за да се появят тук.</p>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
