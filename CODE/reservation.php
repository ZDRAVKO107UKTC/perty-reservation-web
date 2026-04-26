<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$connection = db();
$flash = pull_flash();

$tables = $connection->query(
    'SELECT id_tables, table_number, seats_number
     FROM tables
     ORDER BY table_number ASC'
)->fetchAll();

$tableCount = count($tables);
$tablesByNumber = [];
foreach ($tables as $table) {
    $tablesByNumber[(int) $table['table_number']] = $table;
}

$parties = $connection->query(
    'SELECT p.id_party,
            p.name_party,
            p.event_date,
            p.description,
            COUNT(r.id_reservation) AS reserved_tables
     FROM parties p
     LEFT JOIN reservation r ON r.id_party = p.id_party
     WHERE p.event_date >= NOW()
     GROUP BY p.id_party, p.name_party, p.event_date, p.description
     ORDER BY p.event_date ASC'
)->fetchAll();

$partyIds = array_map(static fn(array $party): int => (int) $party['id_party'], $parties);
$currentParty = (int) ($_GET['party'] ?? ($partyIds[0] ?? 0));

if ($currentParty !== 0 && !in_array($currentParty, $partyIds, true)) {
    $currentParty = $partyIds[0] ?? 0;
}

$userReservationStatement = $connection->prepare(
    'SELECT r.id_reservation,
            r.id_party,
            t.table_number,
            t.seats_number,
            p.name_party,
            p.event_date
     FROM reservation r
     JOIN tables t ON t.id_tables = r.id_table
     JOIN parties p ON p.id_party = r.id_party
     WHERE r.id_person = ? AND p.event_date >= NOW()'
);
$userReservationStatement->execute([current_user_id()]);

$userReservationsByParty = [];
foreach ($userReservationStatement->fetchAll() as $reservation) {
    $userReservationsByParty[(int) $reservation['id_party']] = $reservation;
}

if (is_post()) {
    require_valid_csrf();

    $partyId = (int) ($_POST['id_party'] ?? 0);
    $tableNumber = (int) ($_POST['table_number'] ?? 0);

    $partyStatement = $connection->prepare(
        'SELECT id_party, name_party, event_date
         FROM parties
         WHERE id_party = ? AND event_date >= NOW()
         LIMIT 1'
    );
    $partyStatement->execute([$partyId]);
    $selectedParty = $partyStatement->fetch();

    if (!$selectedParty) {
        set_flash('danger', 'Избраното събитие вече не е активно за резервации.');
        redirect('reservation.php');
    }

    $existingUserReservation = $connection->prepare(
        'SELECT t.table_number
         FROM reservation r
         JOIN tables t ON t.id_tables = r.id_table
         WHERE r.id_person = ? AND r.id_party = ?
         LIMIT 1'
    );
    $existingUserReservation->execute([current_user_id(), $partyId]);
    $existingReservation = $existingUserReservation->fetch();

    if ($existingReservation) {
        set_flash(
            'warning',
            'Вече имате резервация за това събитие на маса №' . $existingReservation['table_number'] . '. Ако искате друга, отменете първо текущата от профила си.'
        );
        redirect('reservation.php?party=' . $partyId);
    }

    $selectedTable = $connection->prepare(
        'SELECT id_tables, table_number, seats_number
         FROM tables
         WHERE table_number = ?
         LIMIT 1'
    );
    $selectedTable->execute([$tableNumber]);
    $table = $selectedTable->fetch();

    if (!$table) {
        set_flash('danger', 'Избраната маса не беше намерена.');
        redirect('reservation.php?party=' . $partyId);
    }

    try {
        $connection->beginTransaction();

        $lockedReservation = $connection->prepare(
            'SELECT id_reservation
             FROM reservation
             WHERE id_party = ? AND id_table = ?
             LIMIT 1
             FOR UPDATE'
        );
        $lockedReservation->execute([$partyId, $table['id_tables']]);

        if ($lockedReservation->fetch()) {
            $connection->rollBack();
            set_flash('warning', 'Маса №' . $table['table_number'] . ' вече е заета за това събитие.');
            redirect('reservation.php?party=' . $partyId);
        }

        $insert = $connection->prepare(
            'INSERT INTO reservation (id_person, id_table, id_party, date_for_reservation)
             VALUES (?, ?, ?, NOW())'
        );
        $insert->execute([current_user_id(), $table['id_tables'], $partyId]);
        $connection->commit();

        set_flash(
            'success',
            'Маса №' . $table['table_number'] . ' беше резервирана успешно за ' . $selectedParty['name_party'] . '.'
        );
    } catch (PDOException $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }

        set_flash(
            'danger',
            reservation_write_error_message(
                $exception,
                'Възникна проблем при записването на резервацията. Опитайте отново.'
            )
        );
    }

    redirect('reservation.php?party=' . $partyId);
}

$currentPartyData = null;
foreach ($parties as $party) {
    if ((int) $party['id_party'] === $currentParty) {
        $currentPartyData = $party;
        break;
    }
}

$reservedTableNumbers = [];
if ($currentParty !== 0) {
    $reservedTableStatement = $connection->prepare(
        'SELECT t.table_number
         FROM reservation r
         JOIN tables t ON t.id_tables = r.id_table
         WHERE r.id_party = ?'
    );
    $reservedTableStatement->execute([$currentParty]);
    $reservedTableNumbers = array_map('intval', $reservedTableStatement->fetchAll(PDO::FETCH_COLUMN));
}

$reservedLookup = array_fill_keys($reservedTableNumbers, true);
$currentUserReservation = $userReservationsByParty[$currentParty] ?? null;
$availableCount = max($tableCount - count($reservedTableNumbers), 0);
$areas = reservation_table_areas();
$eventImages = [
    'images/event/ev1.jpg',
    'images/event/ev2.jpg',
    'images/event/ev3.jpg',
    'images/event/ev4.jpg',
];
$selectedImage = $eventImages[$currentParty ? (($currentParty - 1) % count($eventImages)) : 0];
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MyClub | Резервации</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="css/site-refresh.css?v=20260423-status">
  <style>
    /* ── Status colour palette ─────────────────────────────────────────────
       is-free    → Emerald green  (#16a34a bg / #dcfce7 fill)
       is-reserved→ Amber-red      (#dc2626 bg / #fee2e2 fill)
       is-owned   → Violet         (#7c3aed bg / #ede9fe fill)
    ──────────────────────────────────────────────────────────────────────── */

    /* ── Status pills (sidebar header) ── */
    .status-pill.is-success {
      background: #dcfce7;
      color: #14532d;
      border: 1px solid #86efac;
    }
    .status-pill.is-danger {
      background: #fee2e2;
      color: #7f1d1d;
      border: 1px solid #fca5a5;
    }

    /* ── Table cards (grid) ── */
    .table-card.is-free {
      background: #dcfce7;
      border-color: #86efac;
      color: #14532d;
    }
    .table-card.is-free button {
      color: #14532d;
    }
    .table-card.is-free:hover {
      background: #bbf7d0;
      border-color: #4ade80;
    }

    .table-card.is-reserved,
    .table-card.is-reserved.is-disabled {
      background: #fee2e2;
      border-color: #fca5a5;
      color: #7f1d1d;
      opacity: 0.75;
    }
    .table-card.is-reserved button,
    .table-card.is-reserved.is-disabled button {
      color: #7f1d1d;
    }

    .table-card.is-owned {
      background: #ede9fe;
      border-color: #c4b5fd;
      color: #3b0764;
    }
    .table-card.is-owned button {
      color: #3b0764;
    }

    /* ── Map overlays ── */
    .reservation-overlay.is-free {
      background: rgba(22, 163, 74, 0.55);
      border: 2px solid #16a34a;
      color: #fff;
    }
    .reservation-overlay.is-reserved {
      background: rgba(220, 38, 38, 0.60);
      border: 2px solid #dc2626;
      color: #fff;
    }
    .reservation-overlay.is-owned {
      background: rgba(124, 58, 237, 0.65);
      border: 2px solid #7c3aed;
      color: #fff;
    }
  </style>
</head>
<body class="brand-body">
  <div class="site-nav">
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">
          <span class="brand-mark"><img src="images/logo.jpg" alt="MyClub logo"></span>
          <span class="brand-copy">
            <strong class="brand-font">MyClub</strong>
            <span>Резервации по събития</span>
          </span>
        </a>
        <button class="navbar-toggler btn btn-ghost-light" type="button" data-bs-toggle="collapse" data-bs-target="#reservationNav" aria-controls="reservationNav" aria-expanded="false" aria-label="Навигация">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="reservationNav">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="index.php">Начало</a></li>
            <li class="nav-item"><a class="nav-link" href="profile.php">Профил</a></li>
            <?php if (current_user_role() === 'admin'): ?>
              <li class="nav-item"><a class="btn btn-ghost-light" href="admin/dashboard.php">Админ панел</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="btn btn-brand" href="logOut.php">Изход</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </div>

  <?php if ($flash): ?>
    <div class="flash-shell">
      <div class="alert alert-<?= e(flash_type_class($flash['type'])) ?> shadow-lg mb-0"><?= e($flash['message']) ?></div>
    </div>
  <?php endif; ?>

  <main class="reservation-shell">
    <div class="container">
      <div class="reservation-layout">
        <aside class="reservation-panel">
          <div class="reservation-hero">
            <span class="eyebrow"><i class="fa-solid fa-table-cells-large"></i> Резервационен панел</span>
            <h1 class="section-title"><?= $currentPartyData ? e($currentPartyData['name_party']) : 'Няма активни събития' ?></h1>
            <?php if ($currentPartyData): ?>
              <p class="section-text"><?= e($currentPartyData['description'] ?: 'Изберете свободна маса от картата или от списъка по-долу.') ?></p>
              <img src="<?= e($selectedImage) ?>" alt="<?= e($currentPartyData['name_party']) ?>" class="rounded-4 border border-light-subtle opacity-75">
              <div class="status-row">
                <span class="meta-pill"><i class="fa-regular fa-clock"></i> <?= e(format_datetime($currentPartyData['event_date'])) ?></span>
                <span class="status-pill is-success"><i class="fa-solid fa-check"></i> Свободни <?= $availableCount ?></span>
                <span class="status-pill is-danger"><i class="fa-solid fa-ban"></i> Заети <?= count($reservedTableNumbers) ?></span>
              </div>
            <?php else: ?>
              <p class="section-text">Администраторът все още не е публикувал бъдещи събития за резервация.</p>
            <?php endif; ?>
          </div>

          <?php if ($parties): ?>
            <form method="get" class="brand-form mt-4">
              <label for="party" class="form-label">Смени събитието</label>
              <select class="form-select" id="party" name="party" onchange="this.form.submit()">
                <?php foreach ($parties as $party): ?>
                  <option value="<?= (int) $party['id_party'] ?>" <?= (int) $party['id_party'] === $currentParty ? 'selected' : '' ?>>
                    <?= e($party['name_party']) ?> - <?= e(format_datetime($party['event_date'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          <?php endif; ?>

          <div class="reservation-summary-grid mt-4">
            <article class="reservation-helper p-3">
              <strong>Какво пази системата</strong>
              <p class="muted-text mb-0">Един профил може да има една активна маса за конкретно събитие. Това избягва дублиране и спорни наличности.</p>
            </article>
            <article class="reservation-helper p-3">
              <strong>Как да смените маса</strong>
              <p class="muted-text mb-0">Отменете текущата си резервация от профила и след това изберете нова маса за същото събитие.</p>
            </article>
            <?php if ($currentUserReservation): ?>
              <article class="reservation-helper p-3">
                <strong>Вашата текуща маса</strong>
                <p class="muted-text mb-0">
                  Вече сте резервирали маса №<?= (int) $currentUserReservation['table_number'] ?>
                  с <?= (int) $currentUserReservation['seats_number'] ?> места за тази вечер.
                </p>
              </article>
            <?php endif; ?>
          </div>

          <div class="action-row mt-4">
            <a href="profile.php" class="btn btn-ghost-light">Към профила</a>
            <a href="index.php#events" class="btn btn-ghost-light">Всички събития</a>
          </div>
        </aside>

        <section class="reservation-stage">
          <div class="section-head mb-4">
            <span class="eyebrow"><i class="fa-solid fa-map"></i> Карта и списък на масите</span>
            <h2 class="section-title">Цветът на масата показва реалната наличност за избраното събитие.</h2>
            <p class="section-text">
              Зелените маси са свободни, червените вече са резервирани, а вашата маса е отбелязана отделно.
              Списъкът под картата служи като бърз и по-достъпен вариант за избор.
            </p>
          </div>

          <?php if ($parties && $tablesByNumber): ?>
            <div class="reservation-map-wrap">
              <img id="venueMap" src="images/reservation/back2.png" class="reservation-map" alt="План на клубната зала" usemap="#venueMapAreas">
              <div id="overlays" class="reservation-overlays"></div>
            </div>
            <map name="venueMapAreas">
              <?php foreach ($areas as $tableNumber => $coords): ?>
                <?php if (!isset($tablesByNumber[$tableNumber])): ?>
                  <?php continue; ?>
                <?php endif; ?>
                <area
                  shape="rect"
                  coords="<?= e($coords) ?>"
                  alt="<?= (int) $tableNumber ?>"
                  href="#reservationModal"
                  data-bs-toggle="modal"
                  data-bs-target="#reservationModal"
                  onclick="return openReservation(event, <?= (int) $tableNumber ?>)"
                >
              <?php endforeach; ?>
            </map>

            <div class="table-grid">
              <?php foreach ($tables as $table): ?>
                <?php
                $tableNumber = (int) $table['table_number'];
                $isReserved = isset($reservedLookup[$tableNumber]);
                $isOwned = $currentUserReservation && (int) $currentUserReservation['table_number'] === $tableNumber;
                $cardClass = $isOwned ? 'is-owned' : ($isReserved ? 'is-reserved is-disabled' : 'is-free');
                ?>
                <article class="table-card <?= $cardClass ?>">
                  <button
                    type="button"
                    <?= ($isReserved || $isOwned) ? 'disabled' : '' ?>
                    data-bs-toggle="modal"
                    data-bs-target="#reservationModal"
                    onclick="return openReservation(event, <?= $tableNumber ?>)"
                  >
                    <strong>Маса №<?= $tableNumber ?></strong>
                    <span><?= (int) $table['seats_number'] ?> места</span>
                    <span>
                      <?php if ($isOwned): ?>
                        Вашата маса
                      <?php elseif ($isReserved): ?>
                        Вече е заета
                      <?php else: ?>
                        Свободна за резервация
                      <?php endif; ?>
                    </span>
                  </button>
                </article>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="glass-panel content-card">
              <h3 class="section-title mb-3">Няма активни резервации за избор</h3>
              <p class="section-text mb-0">Щом бъдат добавени бъдещи събития и маси, тази страница ще покаже пълната карта и наличностите.</p>
            </div>
          <?php endif; ?>
        </section>
      </div>
    </div>
  </main>

  <?php if ($parties && $tablesByNumber): ?>
    <div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
          <form method="post">
            <div class="modal-header">
              <h2 class="modal-title fs-5">Потвърди резервацията</h2>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Затвори"></button>
            </div>
            <div class="modal-body">
              <?= csrf_input() ?>
              <input type="hidden" name="id_party" value="<?= (int) $currentParty ?>">
              <input type="hidden" name="table_number" id="selectedTableNumber">
              <div class="d-grid gap-2">
                <p class="mb-0">Събитие: <strong><?= $currentPartyData ? e($currentPartyData['name_party']) : '-' ?></strong></p>
                <p class="mb-0">Маса: <strong id="selectedTableLabel">-</strong></p>
                <p class="mb-0">Капацитет: <strong id="selectedTableSeats">-</strong></p>
                <p class="mb-0 text-muted">След потвърждение резервацията ще се появи в профила ви.</p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Назад</button>
              <button type="submit" class="btn btn-brand">Потвърди масата</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const tableData = <?= json_encode(array_map(
        static fn(array $table): array => [
            'id' => (int) $table['id_tables'],
            'number' => (int) $table['table_number'],
            'seats' => (int) $table['seats_number'],
        ],
        array_values($tables)
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const tableLookup = Object.fromEntries(tableData.map((table) => [table.number, table]));
    const reservedSet = new Set(<?= json_encode($reservedTableNumbers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>.map(Number));
    const userReservation = <?= json_encode($currentUserReservation ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function showTopAlert(message, type = 'danger') {
      const alert = document.createElement('div');
      alert.className = `alert alert-${type} shadow position-fixed top-0 start-50 translate-middle-x mt-3`;
      alert.style.zIndex = '2000';
      alert.textContent = message;
      document.body.appendChild(alert);
      window.setTimeout(() => alert.remove(), 3400);
    }

    function openReservation(event, tableNumber) {
      const table = tableLookup[tableNumber];

      if (!table) {
        event.preventDefault();
        showTopAlert('Избраната маса не беше намерена.');
        return false;
      }

      if (userReservation && Number(userReservation.table_number) !== tableNumber) {
        event.preventDefault();
        showTopAlert(`Вече имате маса №${userReservation.table_number} за това събитие.`, 'warning');
        return false;
      }

      if (reservedSet.has(tableNumber) && (!userReservation || Number(userReservation.table_number) !== tableNumber)) {
        event.preventDefault();
        showTopAlert(`Маса №${tableNumber} вече е резервирана.`, 'warning');
        return false;
      }

      if (userReservation && Number(userReservation.table_number) === tableNumber) {
        event.preventDefault();
        showTopAlert('Това вече е вашата текуща маса за събитието.', 'warning');
        return false;
      }

      document.getElementById('selectedTableNumber').value = String(tableNumber);
      document.getElementById('selectedTableLabel').textContent = `№${tableNumber}`;
      document.getElementById('selectedTableSeats').textContent = `${table.seats} места`;
      return true;
    }

    function drawOverlays() {
      const image = document.getElementById('venueMap');
      const overlayContainer = document.getElementById('overlays');

      if (!image || !overlayContainer || image.naturalWidth === 0) {
        return;
      }

      const areas = document.querySelectorAll("map[name='venueMapAreas'] area");
      overlayContainer.innerHTML = '';

      const scaleX = image.clientWidth / image.naturalWidth;
      const scaleY = image.clientHeight / image.naturalHeight;

      areas.forEach((area) => {
        const [x1, y1, x2, y2] = area.coords.split(',').map(Number);
        const tableNumber = Number(area.alt);
        const overlay = document.createElement('div');
        const isOwned = userReservation && Number(userReservation.table_number) === tableNumber;
        const isReserved = reservedSet.has(tableNumber);

        overlay.className = 'reservation-overlay ' + (isOwned ? 'is-owned' : (isReserved ? 'is-reserved' : 'is-free'));
        overlay.textContent = tableNumber;
        overlay.style.left = `${x1 * scaleX}px`;
        overlay.style.top = `${y1 * scaleY}px`;
        overlay.style.width = `${(x2 - x1) * scaleX}px`;
        overlay.style.height = `${(y2 - y1) * scaleY}px`;

        overlayContainer.appendChild(overlay);
      });
    }

    window.addEventListener('load', drawOverlays);
    window.addEventListener('resize', drawOverlays);
  </script>
</body>
</html>
