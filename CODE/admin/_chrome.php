<?php

function admin_nav_items(): array
{
    return [
        ['key' => 'dashboard', 'label' => 'Табло', 'href' => 'dashboard.php', 'icon' => 'fa-solid fa-chart-line'],
        ['key' => 'users', 'label' => 'Потребители', 'href' => 'users.php', 'icon' => 'fa-solid fa-users'],
        ['key' => 'reservations', 'label' => 'Резервации', 'href' => 'reservations.php', 'icon' => 'fa-solid fa-table-list'],
        ['key' => 'parties', 'label' => 'Събития', 'href' => 'parties.php', 'icon' => 'fa-solid fa-calendar-days'],
        ['key' => 'messages', 'label' => 'Съобщения', 'href' => 'messages.php', 'icon' => 'fa-solid fa-envelope-open-text'],
    ];
}

function render_admin_header(
    string $title,
    string $active,
    string $heading,
    string $subtitle,
    string $actionsHtml = ''
): void {
    $navItems = admin_nav_items();
    ?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e('MyClub Admin | ' . $title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="../css/site-refresh.css?v=20260423-status">
</head>
<body class="brand-body admin-page">
  <div class="site-nav">
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="dashboard.php">
          <span class="brand-mark"><img src="../images/logo.jpg" alt="MyClub logo"></span>
          <span class="brand-copy">
            <strong class="brand-font">MyClub Admin</strong>
            <span>Управление на събития, резервации и съобщения</span>
          </span>
        </a>
        <button class="navbar-toggler btn btn-ghost-light" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Навигация">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <?php foreach ($navItems as $item): ?>
              <li class="nav-item">
                <a class="nav-link<?= $item['key'] === $active ? ' is-active' : '' ?>" href="<?= e($item['href']) ?>">
                  <i class="<?= e($item['icon']) ?>"></i>
                  <?= e($item['label']) ?>
                </a>
              </li>
            <?php endforeach; ?>
            <li class="nav-item"><a class="btn btn-ghost-light" href="../index.php">Сайт</a></li>
            <li class="nav-item"><a class="btn btn-ghost-light" href="../profile.php">Профил</a></li>
            <li class="nav-item"><a class="btn btn-brand" href="logout.php">Изход</a></li>
          </ul>
        </div>
      </div>
    </nav>
  </div>

  <main class="container admin-shell">
    <section class="glass-panel admin-hero">
      <div class="admin-hero-copy">
        <span class="eyebrow"><i class="fa-solid fa-shield-halved"></i> Админ зона</span>
        <h1 class="section-title mt-3 mb-2"><?= e($heading) ?></h1>
        <p class="section-text mb-0"><?= e($subtitle) ?></p>
      </div>
      <?php if ($actionsHtml !== ''): ?>
        <div class="admin-actions"><?= $actionsHtml ?></div>
      <?php endif; ?>
    </section>
<?php
}

function render_admin_flash(?array $flash): void
{
    if (!$flash) {
        return;
    }

    ?>
    <div class="alert alert-<?= e(flash_type_class($flash['type'])) ?> shadow-sm mb-0"><?= e($flash['message']) ?></div>
<?php
}

function render_admin_footer(): void
{
    ?>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
