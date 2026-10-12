<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>مدیران | ARA Studio</title><link rel="stylesheet" href="/assets/admin.css"></head>
<body class="dashboard-body"><div class="dashboard-shell">
<?php require __DIR__ . '/_navigation.php'; ?>
<main class="admin-content">
  <div class="page-heading"><div><p>دسترسی برابر</p><h1>حساب‌های مدیر</h1><span>همه مدیران دسترسی یکسان دارند. رمزها و نشست‌ها در این صفحه نمایش داده نمی‌شوند.</span></div><a class="primary-link" href="/admin/admins/create">مدیر جدید <span aria-hidden="true">＋</span></a></div>
  <?php if ($notice !== null): ?><p class="success-notice" role="status"><?= e(match ($notice) { 'created' => 'حساب مدیر با موفقیت ایجاد شد.', 'password' => 'رمز عبور تغییر کرد و نشست‌های قبلی آن حساب نامعتبر شدند.', 'activated' => 'حساب مدیر فعال شد.', default => 'حساب مدیر غیرفعال شد و نشست‌های آن نامعتبر شدند.' }) ?></p><?php endif; ?>
  <section class="panel list-panel" aria-labelledby="admins-title"><div class="panel-heading"><div><p><?= e((string) count($admins)) ?> حساب</p><h2 id="admins-title">مدیران ثبت‌شده</h2></div><span>بدون نقش‌بندی</span></div>
    <div class="admin-list">
    <?php foreach ($admins as $record): ?><a class="admin-row" href="/admin/admins/<?= e((string) $record['id']) ?>"><div><strong><bdi><?= e($record['username']) ?></bdi><?= $record['id'] === $currentAdminId ? ' <small>(حساب شما)</small>' : '' ?></strong><span>ایجاد: <bdi><?= e($record['created_at']) ?></bdi> · آخرین تغییر: <bdi><?= e($record['updated_at']) ?></bdi></span></div><span class="status-badge <?= $record['is_active'] === 1 ? 'status-completed' : 'status-archived' ?>"><?= $record['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?></span><span class="row-arrow" aria-hidden="true">←</span></a><?php endforeach; ?>
    </div>
  </section>
</main></div></body></html>
