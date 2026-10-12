<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>جزئیات درخواست #<?= e((string) $request['id']) ?> | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="dashboard-body">
  <div class="dashboard-shell">
    <?php require __DIR__ . '/_navigation.php'; ?>
    <main class="admin-content">
      <div class="page-heading detail-heading">
        <div><a class="eyebrow-link" href="/admin/consultations">درخواست‌های مشاوره ←</a><h1>درخواست شماره <?= e((string) $request['id']) ?></h1><span>ثبت‌شده در <?= e((string) $request['created_at']) ?></span></div>
        <?php $currentStatus = (string) $request['status']; ?><span class="status-badge status-<?= e($currentStatus) ?>"><?= e($statusLabels[$currentStatus] ?? 'نامشخص') ?></span>
      </div>

      <?php if ($notice === 'status'): ?><p class="success-notice" role="status">وضعیت درخواست به‌روزرسانی شد.</p><?php endif; ?>
      <?php if ($notice === 'notes'): ?><p class="success-notice" role="status">یادداشت خصوصی ذخیره شد.</p><?php endif; ?>

      <div class="detail-grid">
        <section class="panel request-detail" aria-labelledby="request-content-title">
          <div class="panel-heading"><div><p>اطلاعات متقاضی</p><h2 id="request-content-title"><?= e((string) $request['name']) ?></h2></div></div>
          <dl class="metadata-grid"><div><dt>راه ارتباطی</dt><dd><bdi><?= e((string) $request['contact']) ?></bdi></dd></div><div><dt>خدمت موردنظر</dt><dd><?= e((string) $request['service']) ?></dd></div><div><dt>زمان ایجاد</dt><dd><?= e((string) $request['created_at']) ?></dd></div><div><dt>آخرین تغییر</dt><dd><?= e((string) $request['updated_at']) ?></dd></div></dl>
          <div class="message-block"><span>توضیحات پروژه</span><p><?= nl2br(e((string) $request['message'])) ?></p></div>
          <?php if ($request['updated_by_username'] !== null): ?><p class="updated-by">آخرین ویرایش توسط <bdi><?= e((string) $request['updated_by_username']) ?></bdi></p><?php endif; ?>
        </section>

        <aside class="detail-actions">
          <section class="panel action-card"><div class="panel-heading"><div><p>گردش کار</p><h2>تغییر وضعیت</h2></div></div>
            <form class="stack-form" method="post" action="/admin/consultations/<?= e((string) $request['id']) ?>/status"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><label for="request-status">وضعیت فعلی</label><select id="request-status" name="status"><?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>"<?= $currentStatus === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="primary-button" type="submit">ذخیره وضعیت</button></form>
          </section>
          <section class="panel action-card"><div class="panel-heading"><div><p>فقط برای مدیران</p><h2>یادداشت خصوصی</h2></div></div>
            <form class="stack-form" method="post" action="/admin/consultations/<?= e((string) $request['id']) ?>/notes"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><label for="admin-notes">یادداشت پیگیری</label><textarea id="admin-notes" name="admin_notes" rows="7" maxlength="5000" placeholder="نکات تماس یا پیگیری بعدی را ثبت کنید."><?= e((string) ($request['admin_notes'] ?? '')) ?></textarea><button class="secondary-button" type="submit">ذخیره یادداشت</button></form>
          </section>
          <details class="danger-zone panel"><summary>حذف درخواست</summary><div><p>این عملیات قابل بازگشت نیست و رکورد را برای همیشه حذف می‌کند.</p><form class="stack-form" method="post" action="/admin/consultations/<?= e((string) $request['id']) ?>/delete"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><label class="confirm-check"><input type="checkbox" name="confirm_delete" value="yes" required> حذف دائمی این درخواست را تأیید می‌کنم.</label><button class="danger-button" type="submit">حذف دائمی</button></form></div></details>
        </aside>
      </div>
    </main>
  </div>
</body>
</html>
