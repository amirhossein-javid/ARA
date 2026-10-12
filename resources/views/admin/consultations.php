<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>درخواست‌های مشاوره | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="dashboard-body">
  <div class="dashboard-shell">
    <?php require __DIR__ . '/_navigation.php'; ?>
    <main class="admin-content">
      <div class="page-heading">
        <div><p>مدیریت ارتباطات</p><h1>درخواست‌های مشاوره</h1><span>جست‌وجو، بررسی و مدیریت روند پیگیری پروژه‌ها.</span></div>
      </div>

      <?php if ($notice === 'deleted'): ?><p class="success-notice" role="status">درخواست مشاوره با موفقیت حذف شد.</p><?php endif; ?>

      <section class="panel filter-panel" aria-label="جست‌وجو و فیلتر">
        <form class="filter-form" method="get" action="/admin/consultations">
          <div class="filter-search"><label for="request-search">جست‌وجو</label><input id="request-search" name="q" type="search" value="<?= e($filters['q']) ?>" maxlength="100" placeholder="نام، تماس، خدمت یا متن درخواست"></div>
          <div><label for="status-filter">وضعیت</label><select id="status-filter" name="status"><option value="">همه وضعیت‌ها</option><?php foreach ($statusLabels as $value => $label): ?><option value="<?= e($value) ?>"<?= $filters['status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
          <button class="primary-button" type="submit">اعمال فیلتر</button>
          <?php if ($filters['q'] !== '' || $filters['status'] !== ''): ?><a class="clear-filter" href="/admin/consultations">پاک‌کردن</a><?php endif; ?>
        </form>
      </section>

      <section class="panel list-panel" aria-labelledby="list-title">
        <div class="panel-heading"><div><p><?= e((string) $pagination['total']) ?> نتیجه</p><h2 id="list-title">فهرست درخواست‌ها</h2></div><span>صفحه <?= e((string) $pagination['page']) ?> از <?= e((string) $pagination['pages']) ?></span></div>
        <?php if ($requests === []): ?>
          <div class="empty-state"><strong>نتیجه‌ای پیدا نشد.</strong><span>عبارت جست‌وجو یا فیلتر وضعیت را تغییر دهید.</span></div>
        <?php else: ?>
          <div class="request-list">
            <?php foreach ($requests as $request): ?><?php $status = (string) $request['status']; ?>
              <a class="request-row" href="/admin/consultations/<?= e((string) $request['id']) ?>">
                <div class="request-person"><strong><?= e((string) $request['name']) ?></strong><span><bdi><?= e((string) $request['contact']) ?></bdi></span></div>
                <span class="request-service"><?= e((string) $request['service']) ?></span>
                <span class="status-badge status-<?= e($status) ?>"><?= e($statusLabels[$status] ?? 'نامشخص') ?></span>
                <time datetime="<?= e((string) $request['created_at']) ?>"><?= e((string) $request['created_at']) ?></time><span class="row-arrow" aria-hidden="true">←</span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($pagination['pages'] > 1): ?>
          <nav class="pagination" aria-label="صفحه‌بندی درخواست‌ها">
            <?php if ($pagination['page'] > 1): ?><a href="?<?= e(http_build_query(['q' => $filters['q'], 'status' => $filters['status'], 'page' => $pagination['page'] - 1])) ?>">صفحه قبل</a><?php endif; ?>
            <span><?= e((string) $pagination['page']) ?> / <?= e((string) $pagination['pages']) ?></span>
            <?php if ($pagination['page'] < $pagination['pages']): ?><a href="?<?= e(http_build_query(['q' => $filters['q'], 'status' => $filters['status'], 'page' => $pagination['page'] + 1])) ?>">صفحه بعد</a><?php endif; ?>
          </nav>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
