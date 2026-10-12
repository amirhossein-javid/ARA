<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>داشبورد مدیریت | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="dashboard-body">
  <div class="dashboard-shell">
    <?php require __DIR__ . '/_navigation.php'; ?>
    <main class="admin-content">
      <div class="page-heading">
        <div><p>نمای کلی</p><h1>داشبورد مدیریت</h1><span>وضعیت درخواست‌های مشاوره را در یک نگاه بررسی کنید.</span></div>
        <a class="primary-link" href="/admin/consultations">مدیریت درخواست‌ها <span aria-hidden="true">←</span></a>
      </div>
      <section class="metric-grid" aria-label="آمار درخواست‌ها">
        <article class="metric-card"><span>کل درخواست‌ها</span><strong><?= e((string) $stats['total']) ?></strong><small>تمام رکوردهای ثبت‌شده</small></article>
        <article class="metric-card metric-new"><span>درخواست‌های جدید</span><strong><?= e((string) $stats['new']) ?></strong><small>در انتظار اولین بررسی</small></article>
        <article class="metric-card metric-progress"><span>نیازمند پیگیری</span><strong><?= e((string) $stats['follow_up']) ?></strong><small>در وضعیت پیگیری</small></article>
        <article class="metric-card"><span>ثبت‌های اخیر</span><strong><?= e((string) $stats['recent']) ?></strong><small>هفت روز گذشته</small></article>
      </section>
      <section class="panel recent-panel" aria-labelledby="recent-title">
        <div class="panel-heading"><div><p>آخرین ورودی‌ها</p><h2 id="recent-title">درخواست‌های اخیر</h2></div><a href="/admin/consultations">مشاهده همه</a></div>
        <?php if ($recentRequests === []): ?>
          <div class="empty-state"><strong>هنوز درخواستی ثبت نشده است.</strong><span>پس از ثبت فرم مشاوره، درخواست‌ها اینجا نمایش داده می‌شوند.</span></div>
        <?php else: ?>
          <div class="request-list">
            <?php foreach ($recentRequests as $request): ?><?php $status = (string) $request['status']; ?>
              <a class="request-row" href="/admin/consultations/<?= e((string) $request['id']) ?>">
                <div class="request-person"><strong><?= e((string) $request['name']) ?></strong><span><bdi><?= e((string) $request['contact']) ?></bdi></span></div>
                <span class="request-service"><?= e((string) $request['service']) ?></span>
                <span class="status-badge status-<?= e($status) ?>"><?= e($statusLabels[$status] ?? 'نامشخص') ?></span>
                <time datetime="<?= e((string) $request['created_at']) ?>"><?= e((string) $request['created_at']) ?></time><span class="row-arrow" aria-hidden="true">←</span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
