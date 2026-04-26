<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$flash = pull_flash();
$reservations = $connection->query(
    'SELECT r.id_reservation,
            r.date_for_reservation,
            u.first_name_person,
            u.last_name_person,
            u.email_person,
            p.name_party,
            p.event_date,
            t.table_number,
            t.seats_number,
            CASE WHEN p.event_date >= NOW() THEN 1 ELSE 0 END AS is_active
     FROM reservation r
     JOIN registered_person u ON r.id_person = u.id_person
     JOIN parties p ON r.id_party = p.id_party
     JOIN tables t ON r.id_table = t.id_tables
     ORDER BY p.event_date ASC, t.table_number ASC'
)->fetchAll();

$actions = '<a href="dashboard.php" class="btn btn-ghost-light">Към таблото</a>'
    . '<a href="parties.php" class="btn btn-brand">Управление на събития</a>';

render_admin_header(
    'Резервации',
    'reservations',
    'Резервации',
    'Преглеждайте всички резервации, като статусът ясно показва дали събитието предстои или вече е минало.',
    $actions
);
render_admin_flash($flash);
?>

<section class="admin-card">
  <h2 class="h5 mb-3">Как да четете този екран</h2>
  <ul class="admin-note-list">
    <li>Статусът показва дали резервацията е за предстоящо събитие или остава само като история.</li>
    <li>Редакцията позволява корекция на потребител, събитие, маса и дата на запис, без да нарушава правилото за една маса на потребител за събитие.</li>
  </ul>
</section>

<section class="admin-card p-0">
  <?php if ($reservations): ?>
    <div class="admin-table-shell">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Потребител</th>
              <th>Събитие</th>
              <th>Дата на събитие</th>
              <th>Маса</th>
              <th>Места</th>
              <th>Създадена</th>
              <th>Статус</th>
              <th class="text-end">Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $reservation): ?>
              <?php $isActive = (bool) $reservation['is_active']; ?>
              <tr>
                <td data-label="ID"><?= (int) $reservation['id_reservation'] ?></td>
                <td data-label="Потребител">
                  <div class="fw-semibold"><?= e($reservation['first_name_person'] . ' ' . $reservation['last_name_person']) ?></div>
                  <div class="text-muted small"><?= e($reservation['email_person']) ?></div>
                </td>
                <td data-label="Събитие"><?= e($reservation['name_party']) ?></td>
                <td data-label="Дата на събитие"><?= e(format_datetime($reservation['event_date'])) ?></td>
                <td data-label="Маса"><?= (int) $reservation['table_number'] ?></td>
                <td data-label="Места"><?= (int) $reservation['seats_number'] ?></td>
                <td data-label="Създадена"><?= e(format_datetime($reservation['date_for_reservation'])) ?></td>
                <td class="status-cell" data-label="Статус">
                  <span class="status-pill <?= $isActive ? 'is-success' : 'is-warning' ?>">
                    <?= $isActive ? 'Предстои' : 'Минало събитие' ?>
                  </span>
                </td>
                <td class="text-end" data-label="Действия">
                  <div class="admin-inline-actions">
                    <a href="edit_reservation.php?id=<?= (int) $reservation['id_reservation'] ?>" class="btn btn-sm btn-ghost-light">Редактирай</a>
                    <a href="delete_reservation.php?id=<?= (int) $reservation['id_reservation'] ?>" class="btn btn-sm btn-outline-danger">Изтрий</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php else: ?>
    <div class="admin-card">
      <p class="muted-text mb-0">Все още няма записани резервации.</p>
    </div>
  <?php endif; ?>
</section>

<?php render_admin_footer(); ?>
