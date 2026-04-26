<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$flash = pull_flash();
$editId = (int) ($_GET['edit'] ?? 0);

if (is_post()) {
    require_valid_csrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'save_party') {
        $partyId = (int) ($_POST['id_party'] ?? 0);
        $name = trim($_POST['name_party'] ?? '');
        $eventDate = parse_datetime_local($_POST['event_date'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '' || $eventDate === null) {
            set_flash('danger', 'Моля, въведете име и валидна дата за събитието.');
            redirect('admin/parties.php' . ($partyId > 0 ? '?edit=' . $partyId : ''));
        }

        if ($partyId > 0) {
            $update = $connection->prepare(
                'UPDATE parties
                 SET name_party = ?, event_date = ?, description = ?
                 WHERE id_party = ?'
            );
            $update->execute([$name, $eventDate, $description, $partyId]);
            set_flash('success', 'Събитието беше обновено.');
        } else {
            $insert = $connection->prepare(
                'INSERT INTO parties (name_party, event_date, description, created_at)
                 VALUES (?, ?, ?, NOW())'
            );
            $insert->execute([$name, $eventDate, $description]);
            set_flash('success', 'Събитието беше добавено.');
        }

        redirect('admin/parties.php');
    }

    if ($action === 'delete_party') {
        $partyId = (int) ($_POST['id_party'] ?? 0);
        $delete = $connection->prepare('DELETE FROM parties WHERE id_party = ?');
        $delete->execute([$partyId]);

        set_flash('success', 'Събитието беше изтрито.');
        redirect('admin/parties.php');
    }
}

$editParty = [
    'id_party' => 0,
    'name_party' => '',
    'event_date' => '',
    'description' => '',
];

if ($editId > 0) {
    $editStatement = $connection->prepare(
        'SELECT id_party, name_party, event_date, description
         FROM parties
         WHERE id_party = ?
         LIMIT 1'
    );
    $editStatement->execute([$editId]);
    $foundParty = $editStatement->fetch();

    if ($foundParty) {
        $editParty = $foundParty;
    } else {
        set_flash('warning', 'Събитието за редакция не беше намерено.');
        redirect('admin/parties.php');
    }
}

$parties = $connection->query(
    'SELECT p.id_party,
            p.name_party,
            p.event_date,
            p.description,
            p.created_at,
            COUNT(r.id_reservation) AS reservation_count,
            CASE WHEN p.event_date >= NOW() THEN 1 ELSE 0 END AS is_upcoming
     FROM parties p
     LEFT JOIN reservation r ON r.id_party = p.id_party
     GROUP BY p.id_party, p.name_party, p.event_date, p.description, p.created_at
     ORDER BY p.event_date ASC'
)->fetchAll();

$actions = '<a href="dashboard.php" class="btn btn-ghost-light">Към таблото</a>'
    . '<a href="reservations.php" class="btn btn-brand">Към резервациите</a>';

render_admin_header(
    'Събития',
    'parties',
    'Събития',
    'Управлявайте календара на клуба. Само бъдещите събития се показват на началната страница и в публичния екран за резервации.',
    $actions
);
render_admin_flash($flash);
?>

<section class="admin-split">
  <div class="admin-stack">
    <article class="admin-card">
      <h2 class="h5 mb-3"><?= $editParty['id_party'] ? 'Редакция на събитие' : 'Ново събитие' ?></h2>
      <form method="post" class="row g-3">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="save_party">
        <input type="hidden" name="id_party" value="<?= (int) $editParty['id_party'] ?>">
        <div class="col-12">
          <label for="name_party" class="form-label">Име</label>
          <input type="text" class="form-control" id="name_party" name="name_party" value="<?= e($editParty['name_party']) ?>" required>
        </div>
        <div class="col-12">
          <label for="event_date" class="form-label">Дата и час</label>
          <input type="datetime-local" class="form-control" id="event_date" name="event_date" value="<?= e(format_datetime_local_value($editParty['event_date'])) ?>" required>
        </div>
        <div class="col-12">
          <label for="description" class="form-label">Описание</label>
          <textarea class="form-control" id="description" name="description" rows="5"><?= e($editParty['description']) ?></textarea>
        </div>
        <div class="col-12 d-flex flex-wrap gap-2">
          <button type="submit" class="btn btn-brand"><?= $editParty['id_party'] ? 'Запази промените' : 'Добави събитие' ?></button>
          <?php if ($editParty['id_party']): ?>
            <a href="parties.php" class="btn btn-ghost-light">Отказ</a>
          <?php endif; ?>
        </div>
      </form>
    </article>

    <article class="admin-card">
      <h2 class="h5 mb-3">Полезни уточнения</h2>
      <ul class="admin-note-list">
        <li>Ако изтриете събитие, свързаните с него резервации също ще бъдат премахнати.</li>
        <li>Миналите събития остават видими тук за история, но не се показват публично за нови резервации.</li>
      </ul>
    </article>
  </div>

  <article class="admin-card p-0">
    <div class="admin-table-shell">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Събитие</th>
              <th>Дата</th>
              <th>Резервации</th>
              <th>Статус</th>
              <th class="text-end">Действия</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($parties as $party): ?>
              <?php $isUpcoming = (bool) $party['is_upcoming']; ?>
              <tr>
                <td data-label="ID"><?= (int) $party['id_party'] ?></td>
                <td data-label="Събитие">
                  <div class="fw-semibold"><?= e($party['name_party']) ?></div>
                  <div class="text-muted small"><?= e($party['description'] ?: 'Без описание') ?></div>
                </td>
                <td data-label="Дата"><?= e(format_datetime($party['event_date'])) ?></td>
                <td data-label="Резервации"><?= (int) $party['reservation_count'] ?></td>
                <td class="status-cell" data-label="Статус">
                  <span class="status-pill <?= $isUpcoming ? 'is-success' : 'is-warning' ?>">
                    <?= $isUpcoming ? 'Публично видимо' : 'Само история' ?>
                  </span>
                </td>
                <td class="text-end" data-label="Действия">
                  <div class="admin-inline-actions">
                    <a href="parties.php?edit=<?= (int) $party['id_party'] ?>" class="btn btn-sm btn-ghost-light">Редактирай</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Изтриването на събитие ще премахне и свързаните резервации. Продължавате ли?');">
                      <?= csrf_input() ?>
                      <input type="hidden" name="action" value="delete_party">
                      <input type="hidden" name="id_party" value="<?= (int) $party['id_party'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Изтрий</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </article>
</section>

<?php render_admin_footer(); ?>
