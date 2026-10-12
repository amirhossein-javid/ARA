<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>مدیریت نمونه‌کارها | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="dashboard-body">
  <div class="dashboard-shell">
    <?php require __DIR__ . '/_navigation.php'; ?>
    <main class="admin-content">
      <div class="page-heading">
        <div><p>محتوای عمومی</p><h1>مدیریت نمونه‌کارها</h1><span>پروژه‌های منتشرشده به‌ترتیب تعیین‌شده در صفحه اصلی نمایش داده می‌شوند.</span></div>
        <a class="primary-link" href="/admin/portfolio/create">افزودن پروژه <span aria-hidden="true">＋</span></a>
      </div>

      <?php if ($notice === 'deleted'): ?><p class="success-notice" role="status">پروژه و تصویر مدیریت‌شده‌ی آن حذف شد.</p><?php endif; ?>
      <?php if ($notice === 'publication'): ?><p class="success-notice" role="status">وضعیت انتشار پروژه تغییر کرد.</p><?php endif; ?>

      <section class="panel filter-panel" aria-label="فیلتر نمونه‌کارها">
        <form class="filter-form portfolio-filter" method="get" action="/admin/portfolio">
          <div><label for="portfolio-status">وضعیت انتشار</label><select id="portfolio-status" name="status"><option value=""<?= $filterStatus === '' ? ' selected' : '' ?>>همه پروژه‌ها</option><option value="published"<?= $filterStatus === 'published' ? ' selected' : '' ?>>منتشرشده</option><option value="draft"<?= $filterStatus === 'draft' ? ' selected' : '' ?>>پیش‌نویس</option></select></div>
          <button class="primary-button" type="submit">اعمال فیلتر</button>
          <?php if ($filterStatus !== ''): ?><a class="clear-filter" href="/admin/portfolio">پاک‌کردن</a><?php endif; ?>
        </form>
      </section>

      <section class="panel list-panel" aria-labelledby="portfolio-list-title">
        <div class="panel-heading"><div><p><?= e((string) $pagination['total']) ?> پروژه</p><h2 id="portfolio-list-title">فهرست نمونه‌کارها</h2></div><span>صفحه <?= e((string) $pagination['page']) ?> از <?= e((string) $pagination['pages']) ?></span></div>
        <?php if ($projects === []): ?>
          <div class="empty-state"><strong>هنوز پروژه‌ای در این وضعیت وجود ندارد.</strong><span>برای جایگزینی نمونه‌های آزمایشی، اولین پروژه واقعی را ایجاد و منتشر کنید.</span><a class="primary-link empty-action" href="/admin/portfolio/create">ایجاد پروژه</a></div>
        <?php else: ?>
          <div class="portfolio-admin-list">
            <?php foreach ($projects as $project): ?>
              <a class="portfolio-admin-row" href="/admin/portfolio/<?= e((string) $project['id']) ?>">
                <div class="portfolio-thumb"><?php if (is_string($project['image_path']) && $project['image_path'] !== ''): ?><img src="/<?= e($project['image_path']) ?>" alt=""><?php else: ?><span>ARA</span><?php endif; ?></div>
                <div class="portfolio-row-copy"><strong><?= e((string) $project['title']) ?></strong><p><?= e((string) $project['short_description']) ?></p></div>
                <span class="status-badge <?= (int) $project['is_published'] === 1 ? 'status-completed' : 'status-archived' ?>"><?= (int) $project['is_published'] === 1 ? 'منتشرشده' : 'پیش‌نویس' ?></span>
                <span class="order-badge">ترتیب <?= e((string) $project['display_order']) ?></span>
                <span class="row-arrow" aria-hidden="true">←</span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($pagination['pages'] > 1): ?><nav class="pagination" aria-label="صفحه‌بندی نمونه‌کارها"><?php if ($pagination['page'] > 1): ?><a href="?<?= e(http_build_query(['status' => $filterStatus, 'page' => $pagination['page'] - 1])) ?>">صفحه قبل</a><?php endif; ?><span><?= e((string) $pagination['page']) ?> / <?= e((string) $pagination['pages']) ?></span><?php if ($pagination['page'] < $pagination['pages']): ?><a href="?<?= e(http_build_query(['status' => $filterStatus, 'page' => $pagination['page'] + 1])) ?>">صفحه بعد</a><?php endif; ?></nav><?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>
