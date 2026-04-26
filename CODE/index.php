<?php
require_once __DIR__ . '/includes/bootstrap.php';

$connection = db();
$flash = pull_flash();
$totalTables = (int) $connection->query('SELECT COUNT(*) FROM tables')->fetchColumn();

$upcomingEvents = $connection->query(
    'SELECT p.id_party,
            p.name_party,
            p.event_date,
            p.description,
            COUNT(r.id_reservation) AS reserved_tables
     FROM parties p
     LEFT JOIN reservation r ON r.id_party = p.id_party
     WHERE p.event_date >= NOW()
     GROUP BY p.id_party, p.name_party, p.event_date, p.description
     ORDER BY p.event_date ASC
     LIMIT 6'
)->fetchAll();

$userReservations = [];
if (is_logged_in()) {
    $userReservationStatement = $connection->prepare(
        'SELECT r.id_party, r.id_reservation, t.table_number
         FROM reservation r
         JOIN tables t ON r.id_table = t.id_tables
         JOIN parties p ON p.id_party = r.id_party
         WHERE r.id_person = ? AND p.event_date >= NOW()'
    );
    $userReservationStatement->execute([current_user_id()]);

    foreach ($userReservationStatement->fetchAll() as $reservation) {
        $userReservations[(int) $reservation['id_party']] = $reservation;
    }
}

$nextEvent = $upcomingEvents[0] ?? null;
$eventImages = [
    'images/event/ev1.jpg',
    'images/event/ev2.jpg',
    'images/event/ev3.jpg',
    'images/event/ev4.jpg',
];

$experienceCards = [
    [
        'icon' => 'fa-solid fa-martini-glass-citrus',
        'title' => 'Коктейлен бар',
        'text' => 'Авторски коктейли, бързо обслужване и ясен фокус върху клубната вечер.',
    ],
    [
        'icon' => 'fa-solid fa-couch',
        'title' => 'Маси и VIP зони',
        'text' => 'Всяка маса е с конкретен капацитет, за да знаете колко места резервирате.',
    ],
    [
        'icon' => 'fa-solid fa-music',
        'title' => 'Кураторска музика',
        'text' => 'Вечерите са организирани като отделни събития с различно настроение и публика.',
    ],
    [
        'icon' => 'fa-solid fa-clock',
        'title' => 'Бърз процес',
        'text' => 'Избирате събитие, виждате свободните маси и потвърждавате директно онлайн.',
    ],
];

$faqCards = [
    [
        'title' => 'Една маса на потребител за събитие',
        'text' => 'За да няма объркване, системата позволява по една активна маса за един профил на конкретно събитие.',
    ],
    [
        'title' => 'Цветовете на картата имат значение',
        'text' => 'Зелените маси са свободни, червените вече са заети, а вашата резервация се отбелязва отделно.',
    ],
    [
        'title' => 'Промени и отказ',
        'text' => 'Ако искате да смените маса, първо отменете текущата си резервация от профила си и след това направете нова.',
    ],
];

$galleryImages = [
    ['path' => 'images/clubPh/cl1.jpg', 'class' => 'wide'],
    ['path' => 'images/clubPh/cl2.jpeg', 'class' => 'tall'],
    ['path' => 'images/clubPh/cl3.jpg', 'class' => 'wide'],
    ['path' => 'images/clubPh/cl4.jpg', 'class' => 'tall'],
];

$conceptCards = [
    [
        'image' => 'images/dj/dj1.jpg',
        'title' => 'Peak Time House',
        'text' => 'Вечери с фокус върху енергичен house сет и плавно покачване на темпото.',
    ],
    [
        'image' => 'images/dj/dj2.jpg',
        'title' => 'Open Format Friday',
        'text' => 'Поп, club edits и хип-хоп акценти за по-широка публика и групови резервации.',
    ],
    [
        'image' => 'images/dj/dj3.jpg',
        'title' => 'Late Night Grooves',
        'text' => 'По-късен старт, по-дълги сетове и клубна атмосфера за хора, които остават до края.',
    ],
];
?>
<!doctype html>
<html lang="bg">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MyClub | Онлайн резервации за клубни събития</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="icon" href="images/fav1.png">
  <link rel="stylesheet" href="css/site-refresh.css?v=20260423-status">
</head>
<body class="brand-body">
  <div class="site-nav">
    <nav class="navbar navbar-expand-lg">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="#home">
          <span class="brand-mark"><img src="images/logo.jpg" alt="MyClub logo"></span>
          <span class="brand-copy">
            <strong class="brand-font">MyClub</strong>
            <span>Клубни събития и онлайн резервации</span>
          </span>
        </a>
        <button class="navbar-toggler btn btn-ghost-light" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="Навигация">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="siteNav">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <li class="nav-item"><a class="nav-link" href="#how-it-works">Как работи</a></li>
            <li class="nav-item"><a class="nav-link" href="#events">Събития</a></li>
            <li class="nav-item"><a class="nav-link" href="#experience">Клубът</a></li>
            <li class="nav-item"><a class="nav-link" href="#contact">Контакт</a></li>
            <?php if (is_logged_in()): ?>
              <li class="nav-item"><a class="btn btn-ghost-light" href="profile.php">Профил</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="btn btn-ghost-light" href="logIn.php">Вход</a></li>
              <li class="nav-item"><a class="btn btn-brand" href="signUp.php">Регистрация</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="btn btn-brand" href="<?= $nextEvent ? 'reservation.php?party=' . (int) $nextEvent['id_party'] : 'reservation.php' ?>">Резервирай</a></li>
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

  <header id="home" class="hero-section">
    <div class="container">
      <div class="hero-layout">
        <section class="glass-panel hero-copy">
          <span class="eyebrow"><i class="fa-solid fa-ticket"></i> Ясни правила. Реални наличности.</span>
          <h1 class="hero-title">Резервирайте маса за следващото клубно събитие без обаждания и без неясни условия.</h1>
          <p class="hero-text">
            MyClub е организиран около конкретни събития, а не около общи запитвания.
            Избирате вечер, виждате свободните маси в реално време и потвърждавате директно от профила си.
          </p>
          <div class="hero-actions">
            <a class="btn btn-brand btn-lg" href="<?= $nextEvent ? 'reservation.php?party=' . (int) $nextEvent['id_party'] : 'reservation.php' ?>">Избери събитие и маса</a>
            <?php if (is_logged_in()): ?>
              <a class="btn btn-ghost-light btn-lg" href="profile.php">Моите резервации</a>
            <?php else: ?>
              <a class="btn btn-ghost-light btn-lg" href="signUp.php">Създай профил</a>
            <?php endif; ?>
          </div>
          <div class="stat-grid">
            <div class="stat-tile">
              <strong><?= count($upcomingEvents) ?></strong>
              <span>предстоящи събития</span>
            </div>
            <div class="stat-tile">
              <strong><?= $totalTables ?></strong>
              <span>маси в клубната зала</span>
            </div>
            <div class="stat-tile">
              <strong><?= count($userReservations) ?></strong>
              <span>ваши активни резервации</span>
            </div>
          </div>
        </section>

        <aside class="glass-panel hero-aside next-event-card">
          <span class="eyebrow"><i class="fa-solid fa-calendar-check"></i> Следващо събитие</span>
          <?php if ($nextEvent): ?>
            <h2 class="section-title"><?= e($nextEvent['name_party']) ?></h2>
            <p class="hero-text"><?= e($nextEvent['description'] ?: 'Събитие с ограничен брой маси и онлайн наличност в реално време.') ?></p>
            <div class="meta">
              <span class="meta-pill"><i class="fa-regular fa-clock"></i> <?= e(format_datetime($nextEvent['event_date'])) ?></span>
              <span class="meta-pill"><i class="fa-solid fa-table-cells-large"></i> Свободни <?= max($totalTables - (int) $nextEvent['reserved_tables'], 0) ?></span>
            </div>
            <div class="glass-panel soft p-3">
              <p class="muted-text mb-2">Какво означава резервацията:</p>
              <ul class="mb-0 ps-3">
                <li>Една маса на профил за конкретно събитие.</li>
                <li>Наличността се пази отделно за всяка вечер.</li>
                <li>Промени и отказ се правят от профила ви.</li>
              </ul>
            </div>
          <?php else: ?>
            <h2 class="section-title">Няма публикувани бъдещи събития</h2>
            <p class="hero-text">Когато администратор добави нова клубна вечер, тя ще се покаже тук за директна резервация.</p>
          <?php endif; ?>
        </aside>
      </div>
    </div>
  </header>

  <main>
    <section id="how-it-works" class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-solid fa-circle-info"></i> Как работи системата</span>
          <h2 class="section-title">Резервационният процес вече не оставя място за предположения.</h2>
          <p class="section-text">Всяка стъпка е създадена така, че посетителят да знае какво резервира, за кое събитие и какво следва след това.</p>
        </div>
        <div class="step-grid">
          <article class="step-card">
            <span class="step-index">01</span>
            <strong>Създавате профил или влизате</strong>
            <p class="muted-text">Резервациите се пазят към вашия акаунт, за да можете да ги преглеждате и отменяте по-късно.</p>
          </article>
          <article class="step-card">
            <span class="step-index">02</span>
            <strong>Избирате събитие</strong>
            <p class="muted-text">В сайта няма „обща“ резервация. Винаги резервирате маса за конкретна дата и конкретна клубна вечер.</p>
          </article>
          <article class="step-card">
            <span class="step-index">03</span>
            <strong>Виждате свободните маси</strong>
            <p class="muted-text">Картата на залата и списъкът с маси показват същата наличност, за да няма разминаване между дизайн и логика.</p>
          </article>
        </div>
      </div>
    </section>

    <section id="events" class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-solid fa-calendar-days"></i> Предстоящи вечери</span>
          <h2 class="section-title">Събитията на началната страница вече са реални записи от системата.</h2>
          <p class="section-text">Няма примерни дати и фиктивни програми. Това, което виждате тук, е това, което може да се резервира.</p>
        </div>

        <?php if ($upcomingEvents): ?>
          <div class="event-grid">
            <?php foreach ($upcomingEvents as $index => $event): ?>
              <?php
              $reservedCount = (int) $event['reserved_tables'];
              $availableCount = max($totalTables - $reservedCount, 0);
              $userReservation = $userReservations[(int) $event['id_party']] ?? null;
              $eventImage = $eventImages[$index % count($eventImages)];
              ?>
              <article class="event-card">
                <div class="event-card-media">
                  <img src="<?= e($eventImage) ?>" alt="<?= e($event['name_party']) ?>">
                </div>
                <div class="event-card-body">
                  <div class="d-flex flex-wrap gap-2">
                    <span class="status-pill <?= $availableCount > 0 ? 'is-success' : 'is-danger' ?>">
                      <i class="fa-solid fa-table-cells-large"></i>
                      <?= $availableCount > 0 ? 'Свободни ' . $availableCount : 'Разпродадено' ?>
                    </span>
                    <?php if ($userReservation): ?>
                      <span class="status-pill is-warning">
                        <i class="fa-solid fa-star"></i>
                        Вашата маса №<?= (int) $userReservation['table_number'] ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <h3 class="event-card-title"><?= e($event['name_party']) ?></h3>
                  <p class="event-card-copy"><?= e($event['description'] ?: 'Клубна вечер с ограничен капацитет и активна онлайн наличност.') ?></p>
                  <div class="event-card-footer">
                    <div>
                      <small>Дата</small>
                      <strong><?= e(format_datetime($event['event_date'])) ?></strong>
                    </div>
                    <div class="d-flex flex-column align-items-end">
                      <?php if ($userReservation): ?>
                        <a class="btn btn-ghost-light" href="profile.php">Виж в профила</a>
                      <?php elseif ($availableCount === 0): ?>
                        <a class="btn btn-ghost-light" href="<?= is_logged_in() ? 'reservation.php?party=' . (int) $event['id_party'] : 'logIn.php' ?>">
                          <?= is_logged_in() ? 'Виж статуса' : 'Влез за статус' ?>
                        </a>
                      <?php elseif (is_logged_in()): ?>
                        <a class="btn btn-brand" href="reservation.php?party=<?= (int) $event['id_party'] ?>">Избери маса</a>
                      <?php else: ?>
                        <a class="btn btn-brand" href="logIn.php">Влез за резервация</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="glass-panel section-card">
            <h3 class="section-title mb-3">Няма активни бъдещи събития</h3>
            <p class="section-text mb-0">След като бъдат добавени нови дати от администратора, те автоматично ще се покажат тук и ще могат да се резервират.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section id="experience" class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-solid fa-champagne-glasses"></i> Атмосферата в клуба</span>
          <h2 class="section-title">Вместо общи обещания, сайтът вече обяснява какво реално получава посетителят.</h2>
          <p class="section-text">Всеки елемент е описан така, че да подготви гостите за клубната вечер, а не да ги обърква с несвързани секции.</p>
        </div>
        <div class="experience-grid">
          <?php foreach ($experienceCards as $card): ?>
            <article class="experience-card">
              <span class="icon-wrap"><i class="<?= e($card['icon']) ?>"></i></span>
              <strong><?= e($card['title']) ?></strong>
              <p class="muted-text"><?= e($card['text']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-regular fa-images"></i> Галерия</span>
          <h2 class="section-title">Визуалната част вече подкрепя резервационния контекст.</h2>
          <p class="section-text">Снимките са оставени като атмосфера, без да се представят като фиктивни партньори, изпълнители или примерни програми.</p>
        </div>
        <div class="gallery-grid">
          <?php foreach ($galleryImages as $image): ?>
            <div class="gallery-item <?= e($image['class']) ?>">
              <img src="<?= e($image['path']) ?>" alt="Интериор и атмосфера в MyClub">
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-solid fa-headphones"></i> Музикални концепции</span>
          <h2 class="section-title">Вместо фиктивни DJ имена, секцията описва реални типове вечери.</h2>
          <p class="section-text">Така сайтът показва настроение и посока, без да подвежда посетителя с измислен лайнап.</p>
        </div>
        <div class="event-grid">
          <?php foreach ($conceptCards as $concept): ?>
            <article class="event-card">
              <div class="event-card-media">
                <img src="<?= e($concept['image']) ?>" alt="<?= e($concept['title']) ?>">
              </div>
              <div class="event-card-body">
                <h3 class="event-card-title"><?= e($concept['title']) ?></h3>
                <p class="event-card-copy"><?= e($concept['text']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-solid fa-check-double"></i> Важни уточнения</span>
          <h2 class="section-title">Най-честите обърквания вече са обяснени директно в интерфейса.</h2>
          <p class="section-text">Това намалява нуждата от обаждания и прави процеса предвидим още преди потребителят да отвори картата на залата.</p>
        </div>
        <div class="faq-grid">
          <?php foreach ($faqCards as $card): ?>
            <article class="faq-card">
              <strong><?= e($card['title']) ?></strong>
              <p class="muted-text"><?= e($card['text']) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section id="contact" class="section-shell">
      <div class="container">
        <div class="section-head">
          <span class="eyebrow"><i class="fa-regular fa-envelope"></i> Контакт</span>
          <h2 class="section-title">Премахнахме фиктивната карта и примерния адрес. Останаха само реалните начини за връзка.</h2>
          <p class="section-text">Контактната форма вече записва запитванията в системата и ги прави видими в администраторския панел.</p>
        </div>
        <div class="contact-grid">
          <div class="contact-stack">
            <article class="contact-card">
              <strong>Кога да използвате формата</strong>
              <p class="muted-text">За въпроси за групи, специални поводи, корпоративни посещения и уточнения извън стандартната онлайн резервация.</p>
            </article>
            <article class="contact-card">
              <strong>Кога да използвате резервационната система</strong>
              <p class="muted-text">Когато искате да запазите маса за публикувано събитие. Там наличността и капацитетът са най-точни.</p>
            </article>
            <article class="contact-card">
              <strong>Какво вижда екипът</strong>
              <p class="muted-text">Име, имейл, тема и съобщение. Така запитванията не се губят и могат да се проследят в админ панела.</p>
            </article>
          </div>

          <div class="glass-panel content-card">
            <form action="send-email.php" method="post" class="brand-form">
              <?= csrf_input() ?>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="name" class="form-label">Име</label>
                  <input type="text" id="name" name="name" class="form-control" placeholder="Вашето име" required>
                </div>
                <div class="col-md-6">
                  <label for="phone" class="form-label">Телефон</label>
                  <input type="text" id="phone" name="phone" class="form-control" placeholder="По желание">
                </div>
                <div class="col-12">
                  <label for="email" class="form-label">Имейл</label>
                  <input type="email" id="email" name="email" class="form-control" placeholder="example@email.com" required>
                </div>
                <div class="col-12">
                  <label for="subject" class="form-label">Тема</label>
                  <input type="text" id="subject" name="subject" class="form-control" placeholder="Например: групова резервация" required>
                </div>
                <div class="col-12">
                  <label for="message" class="form-label">Съобщение</label>
                  <textarea class="form-control" id="message" name="message" placeholder="Опишете какво ви е нужно и за коя дата" required></textarea>
                </div>
                <div class="col-12">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="consent" name="consent" required>
                    <label class="form-check-label" for="consent">
                      Съгласявам се данните ми да бъдат използвани единствено за обратна връзка по това запитване.
                    </label>
                  </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                  <button type="submit" class="btn btn-brand btn-lg">Изпрати запитване</button>
                  <?php if ($nextEvent): ?>
                    <a class="btn btn-ghost-light btn-lg" href="reservation.php?party=<?= (int) $nextEvent['id_party'] ?>">Към резервациите</a>
                  <?php endif; ?>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="row g-3">
        <div class="col-md-4">
          <div class="footer-card">
            <strong>MyClub</strong>
            <p class="mb-0">Сайтът е ориентиран към ясна онлайн резервация за конкретни клубни вечери, а не към общи примерни страници.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="footer-card">
            <strong>Бързи връзки</strong>
            <div class="d-flex flex-wrap gap-3">
              <a href="#how-it-works">Как работи</a>
              <a href="#events">Събития</a>
              <a href="#contact">Контакт</a>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="footer-card">
            <strong>Резервационна логика</strong>
            <p class="mb-0">Наличностите се обновяват по събитие, а профилът ви пази активните резервации и отказите.</p>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
