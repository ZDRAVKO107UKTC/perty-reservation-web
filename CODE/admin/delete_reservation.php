<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$reservationId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$statement = $connection->prepare(
    'SELECT r.id_reservation,
            r.date_for_reservation,
            u.first_name_person,
            u.last_name_person,
            p.name_party,
            p.event_date,
            t.table_number
     FROM reservation r
     JOIN registered_person u ON r.id_person = u.id_person
     JOIN parties p ON r.id_party = p.id_party
     JOIN tables t ON r.id_table = t.id_tables
     WHERE r.id_reservation = ?
     LIMIT 1'
);
$statement->execute([$reservationId]);
$reservation = $statement->fetch();

if (!$reservation) {
    set_flash('warning', 'Резервацията не беше намерена.');
    redirect('admin/reservations.php');
}

if (is_post()) {
    require_valid_csrf();

    $delete = $connection->prepare('DELETE FROM reservation WHERE id_reservation = ?');
    $delete->execute([$reservationId]);

    set_flash('success', 'Резервацията беше изтрита.');
    redirect('admin/reservations.php');
}

$actions = '<a href="reservations.php" class="btn btn-ghost-light">Назад към списъка</a>';

render_admin_header(
    'Изтриване на резервация',
    'reservations',
    'Изтриване на резервация #' . $reservation['id_reservation'],
    'Потвърдете само ако сте сигурни, че искате окончателно да премахнете този запис от системата.',
    $actions
);
?>

<section class="admin-card">
  <h2 class="h5 mb-3">Данни за резервацията</h2>
  <ul class="admin-detail-list">
    <li>
      <strong>Потребител</strong>
      <?= e($reservation['first_name_person'] . ' ' . $reservation['last_name_person']) ?>
    </li>
    <li>
      <strong>Събитие</strong>
      <?= e($reservation['name_party']) ?> на <?= e(format_datetime($reservation['event_date'])) ?>
    </li>
    <li>
      <strong>Маса</strong>
      №<?= (int) $reservation['table_number'] ?>
    </li>
    <li>
      <strong>Създадена</strong>
      <?= e(format_datetime($reservation['date_for_reservation'])) ?>
    </li>
  </ul>

  <form method="post" class="d-flex flex-wrap gap-2 mt-4">
    <?= csrf_input() ?>
    <input type="hidden" name="id" value="<?= (int) $reservation['id_reservation'] ?>">
    <button type="submit" class="btn btn-outline-danger">Потвърди изтриването</button>
    <a href="reservations.php" class="btn btn-ghost-light">Отказ</a>
  </form>
</section>

<?php render_admin_footer(); ?>
