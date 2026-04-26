<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$reservationId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$reservationStatement = $connection->prepare(
    'SELECT id_reservation, id_person, id_party, id_table, date_for_reservation
     FROM reservation
     WHERE id_reservation = ?
     LIMIT 1'
);
$reservationStatement->execute([$reservationId]);
$reservation = $reservationStatement->fetch();

if (!$reservation) {
    set_flash('warning', 'Резервацията не беше намерена.');
    redirect('admin/reservations.php');
}

if (is_post()) {
    require_valid_csrf();

    $personId = (int) ($_POST['id_person'] ?? 0);
    $partyId = (int) ($_POST['id_party'] ?? 0);
    $tableId = (int) ($_POST['id_table'] ?? 0);
    $reservationAt = parse_datetime_local($_POST['date_for_reservation'] ?? '');

    if ($personId <= 0 || $partyId <= 0 || $tableId <= 0 || $reservationAt === null) {
        set_flash('danger', 'Моля, попълнете валидни данни за резервацията.');
        redirect('admin/edit_reservation.php?id=' . $reservationId);
    }

    $collision = $connection->prepare(
        'SELECT id_reservation
         FROM reservation
         WHERE id_party = ? AND id_table = ? AND id_reservation <> ?
         LIMIT 1'
    );
    $collision->execute([$partyId, $tableId, $reservationId]);

    if ($collision->fetch()) {
        set_flash('danger', 'Избраната маса вече е заета за това събитие.');
        redirect('admin/edit_reservation.php?id=' . $reservationId);
    }

    $personCollision = $connection->prepare(
        'SELECT id_reservation
         FROM reservation
         WHERE id_person = ? AND id_party = ? AND id_reservation <> ?
         LIMIT 1'
    );
    $personCollision->execute([$personId, $partyId, $reservationId]);

    if ($personCollision->fetch()) {
        set_flash('danger', 'Избраният потребител вече има активна маса за това събитие.');
        redirect('admin/edit_reservation.php?id=' . $reservationId);
    }

    try {
        $update = $connection->prepare(
            'UPDATE reservation
             SET id_person = ?, id_party = ?, id_table = ?, date_for_reservation = ?
             WHERE id_reservation = ?'
        );
        $update->execute([$personId, $partyId, $tableId, $reservationAt, $reservationId]);
    } catch (PDOException $exception) {
        set_flash(
            'danger',
            reservation_write_error_message($exception, 'Не успяхме да обновим резервацията. Проверете данните и опитайте отново.')
        );
        redirect('admin/edit_reservation.php?id=' . $reservationId);
    }

    set_flash('success', 'Резервацията беше обновена.');
    redirect('admin/reservations.php');
}

$users = $connection->query(
    'SELECT id_person, first_name_person, last_name_person
     FROM registered_person
     ORDER BY first_name_person ASC, last_name_person ASC'
)->fetchAll();

$parties = $connection->query(
    'SELECT id_party, name_party, event_date
     FROM parties
     ORDER BY event_date ASC'
)->fetchAll();

$tables = $connection->query(
    'SELECT id_tables, table_number, seats_number
     FROM tables
     ORDER BY table_number ASC'
)->fetchAll();

$flash = pull_flash();
$actions = '<a href="reservations.php" class="btn btn-ghost-light">Назад към списъка</a>'
    . '<a href="delete_reservation.php?id=' . (int) $reservation['id_reservation'] . '" class="btn btn-outline-danger">Изтриване</a>';

render_admin_header(
    'Редакция на резервация',
    'reservations',
    'Редакция на резервация #' . $reservation['id_reservation'],
    'Променете потребителя, събитието, масата или датата на запис, без да нарушавате правилата за наличност.',
    $actions
);
render_admin_flash($flash);
?>

<section class="admin-split">
  <article class="admin-card">
    <h2 class="h5 mb-3">Форма за редакция</h2>
    <form method="post" class="row g-3">
      <?= csrf_input() ?>
      <input type="hidden" name="id" value="<?= (int) $reservation['id_reservation'] ?>">
      <div class="col-md-6">
        <label for="id_person" class="form-label">Потребител</label>
        <select name="id_person" id="id_person" class="form-select" required>
          <?php foreach ($users as $user): ?>
            <option value="<?= (int) $user['id_person'] ?>" <?= (int) $user['id_person'] === (int) $reservation['id_person'] ? 'selected' : '' ?>>
              <?= e($user['first_name_person'] . ' ' . $user['last_name_person']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label for="id_party" class="form-label">Събитие</label>
        <select name="id_party" id="id_party" class="form-select" required>
          <?php foreach ($parties as $party): ?>
            <option value="<?= (int) $party['id_party'] ?>" <?= (int) $party['id_party'] === (int) $reservation['id_party'] ? 'selected' : '' ?>>
              <?= e($party['name_party']) ?> - <?= e(format_datetime($party['event_date'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label for="id_table" class="form-label">Маса</label>
        <select name="id_table" id="id_table" class="form-select" required>
          <?php foreach ($tables as $table): ?>
            <option value="<?= (int) $table['id_tables'] ?>" <?= (int) $table['id_tables'] === (int) $reservation['id_table'] ? 'selected' : '' ?>>
              Маса <?= (int) $table['table_number'] ?> (<?= (int) $table['seats_number'] ?> места)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label for="date_for_reservation" class="form-label">Дата на запис</label>
        <input type="datetime-local" name="date_for_reservation" id="date_for_reservation" class="form-control" value="<?= e(format_datetime_local_value($reservation['date_for_reservation'])) ?>" required>
      </div>
      <div class="col-12 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-brand">Запази промените</button>
        <a href="reservations.php" class="btn btn-ghost-light">Отказ</a>
      </div>
    </form>
  </article>

  <article class="admin-card">
    <h2 class="h5 mb-3">Какво пази системата</h2>
    <ul class="admin-note-list">
      <li>Една маса не може да бъде записана два пъти за едно и също събитие.</li>
      <li>Един потребител не може да държи повече от една активна маса за конкретно събитие.</li>
      <li>Ако данните нарушават тези правила, записът няма да бъде обновен.</li>
    </ul>
  </article>
</section>

<?php render_admin_footer(); ?>
