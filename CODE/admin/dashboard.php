<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$flash = pull_flash();
$stats = [
    'users' => (int) $connection->query('SELECT COUNT(*) FROM registered_person')->fetchColumn(),
    'active_reservations' => (int) $connection->query(
        'SELECT COUNT(*)
         FROM reservation r
         JOIN parties p ON p.id_party = r.id_party
         WHERE p.event_date >= NOW()'
    )->fetchColumn(),
    'upcoming_parties' => (int) $connection->query('SELECT COUNT(*) FROM parties WHERE event_date >= NOW()')->fetchColumn(),
    'messages' => (int) $connection->query('SELECT COUNT(*) FROM contact_messages')->fetchColumn(),
];

$actions = '<a href="../profile.php" class="btn btn-ghost-light">Моят профил</a>'
    . '<a href="parties.php" class="btn btn-brand">Ново събитие</a>';

render_admin_header(
    'Табло',
    'dashboard',
    'Административно табло',
    'Следете най-важните показатели и управлявайте публичните събития, резервациите и входящите съобщения от едно място.',
    $actions
);
render_admin_flash($flash);
?>

<section class="admin-grid">
  <article class="admin-card admin-kpi">
    <p class="muted-text mb-2">Регистрирани профили</p>
    <strong><?= $stats['users'] ?></strong>
    <a href="users.php" class="admin-card-link">Отвори потребители</a>
  </article>
  <article class="admin-card admin-kpi">
    <p class="muted-text mb-2">Активни резервации</p>
    <strong><?= $stats['active_reservations'] ?></strong>
    <a href="reservations.php" class="admin-card-link">Отвори резервации</a>
  </article>
  <article class="admin-card admin-kpi">
    <p class="muted-text mb-2">Предстоящи събития</p>
    <strong><?= $stats['upcoming_parties'] ?></strong>
    <a href="parties.php" class="admin-card-link">Управлявай събитията</a>
  </article>
  <article class="admin-card admin-kpi">
    <p class="muted-text mb-2">Контактни съобщения</p>
    <strong><?= $stats['messages'] ?></strong>
    <a href="messages.php" class="admin-card-link">Прегледай съобщенията</a>
  </article>
</section>

<section class="admin-split">
  <article class="admin-card">
    <h2 class="h5 mb-3">Бързи действия</h2>
    <div class="d-grid gap-2">
      <a href="reservations.php" class="btn btn-ghost-light">Преглед на резервации</a>
      <a href="users.php" class="btn btn-ghost-light">Управление на потребители</a>
      <a href="parties.php" class="btn btn-ghost-light">Добавяне и редакция на събития</a>
      <a href="messages.php" class="btn btn-ghost-light">Преглед на съобщения</a>
    </div>
  </article>

  <article class="admin-card">
    <h2 class="h5 mb-3">Какво е важно</h2>
    <ul class="admin-note-list">
      <li>Само бъдещите събития се показват на началната страница и в публичния екран за резервации.</li>
      <li>Един профил може да има само една активна маса за конкретно събитие и това правило се пази и от базата данни.</li>
      <li>Контактната форма вече записва заявките в системата, за да не се губят съобщения между сайта и админ зоната.</li>
    </ul>
  </article>
</section>

<?php render_admin_footer(); ?>
