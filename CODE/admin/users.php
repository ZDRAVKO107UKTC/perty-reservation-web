<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$flash = pull_flash();
$currentAdminId = current_user_id();

if (is_post()) {
    require_valid_csrf();

    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);

    $userStatement = $connection->prepare(
        'SELECT id_person, first_name_person, last_name_person, email_person, role
         FROM registered_person
         WHERE id_person = ?
         LIMIT 1'
    );
    $userStatement->execute([$userId]);
    $targetUser = $userStatement->fetch();

    if (!$targetUser) {
        set_flash('warning', 'Избраният потребител не беше намерен.');
        redirect('users.php');
    }

    if ($action === 'update_role') {
        $newRole = normalize_role($_POST['role'] ?? 'user');

        if ($userId === $currentAdminId) {
            set_flash('warning', 'Не можете да променяте собствената си роля от този екран.');
            redirect('users.php');
        }

        $update = $connection->prepare('UPDATE registered_person SET role = ? WHERE id_person = ?');
        $update->execute([$newRole, $userId]);

        set_flash('success', 'Ролята на потребителя беше обновена.');
        redirect('users.php');
    }

    if ($action === 'delete_user') {
        if ($userId === $currentAdminId) {
            set_flash('warning', 'Не можете да изтриете собствения си акаунт от този екран.');
            redirect('users.php');
        }

        if ($targetUser['role'] === 'admin') {
            $adminCount = (int) $connection->query("SELECT COUNT(*) FROM registered_person WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                set_flash('warning', 'Не може да бъде изтрит последният администратор.');
                redirect('users.php');
            }
        }

        $delete = $connection->prepare('DELETE FROM registered_person WHERE id_person = ?');
        $delete->execute([$userId]);

        set_flash('success', 'Потребителят беше изтрит.');
        redirect('users.php');
    }
}

$users = $connection->query(
    "SELECT u.id_person,
            u.first_name_person,
            u.last_name_person,
            u.email_person,
            u.phone_number_person,
            u.role,
            u.created_at,
            COUNT(r.id_reservation) AS reservation_count
     FROM registered_person u
     LEFT JOIN reservation r ON r.id_person = u.id_person
     GROUP BY u.id_person, u.first_name_person, u.last_name_person, u.email_person, u.phone_number_person, u.role, u.created_at
     ORDER BY CASE WHEN u.role = 'admin' THEN 0 ELSE 1 END, u.created_at DESC"
)->fetchAll();
$adminCount = count(array_filter($users, static fn(array $user): bool => $user['role'] === 'admin'));

$actions = '<a href="dashboard.php" class="btn btn-ghost-light">Към таблото</a>'
    . '<a href="../index.php" class="btn btn-brand">Към сайта</a>';

render_admin_header(
    'Потребители',
    'users',
    'Потребители и роли',
    'Преглеждайте профилите, актуализирайте ролите и пазете ясно разграничение между администратори и обикновени потребители.',
    $actions
);
render_admin_flash($flash);
?>

<section class="admin-card">
  <h2 class="h5 mb-3">Вградени защити</h2>
  <ul class="admin-note-list">
    <li>Текущият ви профил не може да бъде изтрит или понижен от този екран.</li>
    <li>Последният администратор в системата не може да бъде премахнат, за да не остане сайтът без админ достъп.</li>
  </ul>
</section>

<section class="admin-card p-0">
  <div class="admin-table-shell">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Име</th>
            <th>Имейл</th>
            <th>Телефон</th>
            <th>Резервации</th>
            <th>Създаден</th>
            <th>Роля</th>
            <th class="text-end">Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <?php
            $isSelf = (int) $user['id_person'] === $currentAdminId;
            $isLastAdmin = $user['role'] === 'admin' && $adminCount <= 1;
            ?>
            <tr>
              <td data-label="ID"><?= (int) $user['id_person'] ?></td>
              <td data-label="Име">
                <div class="fw-semibold"><?= e($user['first_name_person'] . ' ' . $user['last_name_person']) ?></div>
                <?php if ($isSelf): ?>
                  <div class="text-muted small">Текущ профил</div>
                <?php endif; ?>
              </td>
              <td data-label="Имейл"><?= e($user['email_person']) ?></td>
              <td data-label="Телефон"><?= e($user['phone_number_person'] ?: '-') ?></td>
              <td data-label="Резервации"><?= (int) $user['reservation_count'] ?></td>
              <td data-label="Създаден"><?= e(format_datetime($user['created_at'])) ?></td>
              <td class="status-cell" data-label="Роля">
                <span class="status-pill <?= $user['role'] === 'admin' ? 'is-warning' : 'is-success' ?>">
                  <?= e($user['role'] === 'admin' ? 'Администратор' : 'Потребител') ?>
                </span>
              </td>
              <td class="text-end" data-label="Действия">
                <div class="admin-inline-actions">
                  <form method="post" class="d-inline-flex gap-2 align-items-center">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="user_id" value="<?= (int) $user['id_person'] ?>">
                    <select name="role" class="form-select form-select-sm" <?= $isSelf ? 'disabled' : '' ?>>
                      <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Потребител</option>
                      <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Админ</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-ghost-light" <?= $isSelf ? 'disabled' : '' ?>>Запази</button>
                  </form>

                  <form method="post" class="d-inline" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете този потребител?');">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?= (int) $user['id_person'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= ($isSelf || $isLastAdmin) ? 'disabled' : '' ?>>Изтрий</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php render_admin_footer(); ?>
