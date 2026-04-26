<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_chrome.php';

require_admin();

$connection = db();
$flash = pull_flash();

if (is_post()) {
    require_valid_csrf();

    if (($_POST['action'] ?? '') === 'delete_message') {
        $messageId = (int) ($_POST['message_id'] ?? 0);
        $delete = $connection->prepare('DELETE FROM contact_messages WHERE id_contact_message = ?');
        $delete->execute([$messageId]);

        set_flash('success', 'Съобщението беше изтрито.');
        redirect('admin/messages.php');
    }
}

$messages = $connection->query(
    'SELECT id_contact_message, name, phone, email, subject, message, created_at
     FROM contact_messages
     ORDER BY created_at DESC'
)->fetchAll();

$actions = '<a href="dashboard.php" class="btn btn-ghost-light">Към таблото</a>';

render_admin_header(
    'Съобщения',
    'messages',
    'Контактни съобщения',
    'Тук се събират всички заявки от публичната контактна форма, за да има един ясен входящ поток към екипа.',
    $actions
);
render_admin_flash($flash);
?>

<section class="admin-card">
  <h2 class="h5 mb-3">Какво съдържа всяко съобщение</h2>
  <ul class="admin-note-list">
    <li>Име, имейл и тема, за да се ориентирате бързо какво иска посетителят.</li>
    <li>Телефон, когато е оставен доброволно, и точен час на изпращане за по-лесно проследяване.</li>
  </ul>
</section>

<section class="admin-message-grid">
  <?php if ($messages): ?>
    <?php foreach ($messages as $message): ?>
      <article class="admin-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
          <div>
            <h2 class="h5 mb-1"><?= e($message['subject']) ?></h2>
            <p class="text-muted mb-2">
              От <?= e($message['name']) ?> | <?= e($message['email']) ?><?= $message['phone'] ? ' | ' . e($message['phone']) : '' ?>
            </p>
            <p class="mb-3"><?= nl2br(e($message['message'])) ?></p>
            <small class="text-muted">Получено на <?= e(format_datetime($message['created_at'])) ?></small>
          </div>
          <div class="admin-inline-actions">
            <form method="post" onsubmit="return confirm('Сигурни ли сте, че искате да изтриете това съобщение?');">
              <?= csrf_input() ?>
              <input type="hidden" name="action" value="delete_message">
              <input type="hidden" name="message_id" value="<?= (int) $message['id_contact_message'] ?>">
              <button type="submit" class="btn btn-outline-danger">Изтрий</button>
            </form>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  <?php else: ?>
    <article class="admin-card">
      <p class="muted-text mb-0">Все още няма изпратени съобщения от публичната контактна форма.</p>
    </article>
  <?php endif; ?>
</section>

<?php render_admin_footer(); ?>
