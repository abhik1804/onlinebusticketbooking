<?php
/**
 * Admin - Manage FAQs
 * Shreenathji Travels
 */

define('IN_APP', true);
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdminLogin();
$appConfig = require __DIR__ . '/../../config/app.php';
$adminUrl = $appConfig['admin_url'];
$db = getDB();

$msg = '';

// Handle Add FAQ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_faq'])) {
    requireCsrfToken();
    $question = cleanInput($_POST['question'] ?? '');
    $answer = cleanInput($_POST['answer'] ?? '');
    $category = cleanInput($_POST['category'] ?? 'General');

    if (!empty($question) && !empty($answer)) {
        $ins = $db->prepare("INSERT INTO faqs (question, answer, category, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        $ins->execute([$question, $answer, $category]);
        logAdminActivity($currentAdmin['id'], 'add_faq', "Added new FAQ: {$question}");
        $msg = "FAQ added successfully.";
    }
}

// Handle Delete FAQ
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $db->prepare("DELETE FROM faqs WHERE id = ?")->execute([$delId]);
    header("Location: {$adminUrl}/settings/faqs.php?deleted=1");
    exit;
}

$faqs = $db->query("SELECT * FROM faqs ORDER BY sort_order ASC, id ASC")->fetchAll();

$adminPageTitle = "Manage FAQs – Shreenathji Travels";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="admin-main-wrap">
  <header class="admin-top-header">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="btnToggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      <h4 class="fw-bold mb-0">Manage Customer FAQs</h4>
    </div>
  </header>

  <div class="admin-content-body">
    <?php if (!empty($msg) || isset($_GET['deleted'])): ?>
      <div class="alert alert-success py-2 small">FAQ updated successfully.</div>
    <?php endif; ?>

    <div class="row g-4">
      <!-- Left: Add New FAQ Form -->
      <div class="col-lg-5">
        <div class="admin-table-card p-4">
          <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-primary me-2"></i>Add FAQ</h5>
          <form method="POST">
            <?= getCsrfField() ?>
            <input type="hidden" name="action_add_faq" value="1">

            <div class="mb-3">
              <label class="form-label small fw-bold">Question *</label>
              <input type="text" name="question" class="form-control" placeholder="e.g. Can I carry pets in the bus?" required>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Category</label>
              <select name="category" class="form-select">
                <option value="General">General</option>
                <option value="Booking">Booking</option>
                <option value="Cancellation & Refund">Cancellation & Refund</option>
                <option value="Boarding & Travel">Boarding & Travel</option>
                <option value="Safety">Safety</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold">Detailed Answer *</label>
              <textarea name="answer" class="form-control" rows="4" placeholder="Provide accurate and helpful response..." required></textarea>
            </div>

            <button type="submit" class="btn btn-st-primary w-100">Save FAQ</button>
          </form>
        </div>
      </div>

      <!-- Right: List All FAQs -->
      <div class="col-lg-7">
        <div class="admin-table-card">
          <div class="admin-table-header">
            <h5 class="fw-bold mb-0">Published FAQs (<?= count($faqs) ?>)</h5>
          </div>

          <div class="table-responsive">
            <table class="table admin-table align-middle">
              <thead>
                <tr>
                  <th>Question & Answer</th>
                  <th>Category</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($faqs)): ?>
                  <tr><td colspan="3" class="text-center py-4 text-muted">No FAQs found.</td></tr>
                <?php else: ?>
                  <?php foreach ($faqs as $f): ?>
                    <tr>
                      <td>
                        <div class="fw-bold text-dark"><?= e($f['question']) ?></div>
                        <small class="text-muted text-truncate d-block" style="max-width: 350px;"><?= e($f['answer']) ?></small>
                      </td>
                      <td>
                        <span class="badge bg-light text-secondary border"><?= e($f['category']) ?></span>
                      </td>
                      <td class="text-end">
                        <a href="<?= $adminUrl ?>/settings/faqs.php?delete_id=<?= $f['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this FAQ?');">
                          <i class="fas fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
