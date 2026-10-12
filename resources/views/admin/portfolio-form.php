<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= $isCreate ? 'پروژه جدید' : 'ویرایش پروژه' ?> | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="dashboard-body">
  <div class="dashboard-shell">
    <?php require __DIR__ . '/_navigation.php'; ?>
    <main class="admin-content">
      <div class="page-heading detail-heading">
        <div><a class="eyebrow-link" href="/admin/portfolio">مدیریت نمونه‌کارها ←</a><h1><?= $isCreate ? 'افزودن پروژه جدید' : e((string) $project['title']) ?></h1><span><?= $isCreate ? 'اطلاعات واقعی پروژه را ثبت کنید؛ پروژه به‌صورت پیش‌نویس قابل ذخیره است.' : 'اطلاعات، تصویر، لینک تلگرام و ترتیب نمایش این پروژه را مدیریت کنید.' ?></span></div>
        <?php if (!$isCreate): ?><span class="status-badge <?= (int) $project['is_published'] === 1 ? 'status-completed' : 'status-archived' ?>"><?= (int) $project['is_published'] === 1 ? 'منتشرشده' : 'پیش‌نویس' ?></span><?php endif; ?>
      </div>

      <?php if ($error !== null): ?><p class="auth-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
      <?php if (($notice ?? null) === 'created'): ?><p class="success-notice" role="status">پروژه ایجاد شد.</p><?php endif; ?>
      <?php if (($notice ?? null) === 'updated'): ?><p class="success-notice" role="status">اطلاعات پروژه ذخیره شد.</p><?php endif; ?>
      <?php if (($notice ?? null) === 'image'): ?><p class="success-notice" role="status">اطلاعات و تصویر پروژه به‌روزرسانی شد.</p><?php endif; ?>
      <?php if (($notice ?? null) === 'publication'): ?><p class="success-notice" role="status">وضعیت انتشار پروژه تغییر کرد.</p><?php endif; ?>

      <div class="portfolio-editor-grid">
        <section class="panel portfolio-editor">
          <div class="panel-heading"><div><p>اطلاعات پروژه</p><h2><?= $isCreate ? 'پروژه جدید' : 'ویرایش محتوا' ?></h2></div></div>
          <form class="portfolio-form" method="post" enctype="multipart/form-data" action="<?= $isCreate ? '/admin/portfolio/create' : '/admin/portfolio/' . e((string) $project['id']) . '/update' ?>">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="form-field-wide"><label for="portfolio-title">عنوان پروژه</label><input id="portfolio-title" name="title" type="text" value="<?= e((string) $project['title']) ?>" minlength="2" maxlength="160" required></div>
            <div class="form-field-wide"><label for="portfolio-description">توضیح کوتاه</label><textarea id="portfolio-description" name="short_description" rows="5" minlength="10" maxlength="500" required><?= e((string) $project['short_description']) ?></textarea><small>حداکثر ۵۰۰ نویسه؛ این متن در کارت عمومی نمایش داده می‌شود.</small></div>
            <div class="form-field-wide"><label for="telegram-url">لینک پست تلگرام</label><input id="telegram-url" name="telegram_url" type="url" dir="ltr" value="<?= e((string) ($project['telegram_url'] ?? '')) ?>" maxlength="500" placeholder="https://t.me/channel/123"><small>اختیاری؛ فقط لینک مستقیم HTTPS پست در t.me پذیرفته می‌شود.</small></div>
            <div><label for="display-order">ترتیب نمایش</label><input id="display-order" name="display_order" type="number" min="0" max="1000000" value="<?= e((string) $project['display_order']) ?>" required></div>
            <div><label for="publication-status">وضعیت انتشار</label><select id="publication-status" name="is_published"><option value="0"<?= (int) $project['is_published'] === 0 ? ' selected' : '' ?>>پیش‌نویس</option><option value="1"<?= (int) $project['is_published'] === 1 ? ' selected' : '' ?>>منتشرشده</option></select></div>
            <div class="form-field-wide upload-field"><label for="project-image"><?= $isCreate ? 'تصویر پروژه' : 'جایگزینی تصویر' ?></label><input id="project-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPEG، PNG یا WebP؛ حداکثر ۵ مگابایت و ۶۰۰۰×۶۰۰۰ پیکسل.</small></div>
            <?php if (!$isCreate && is_string($project['image_path']) && $project['image_path'] !== ''): ?><label class="confirm-check form-field-wide"><input type="checkbox" name="remove_image" value="yes"> تصویر فعلی پس از ذخیره حذف شود.</label><?php endif; ?>
            <button class="primary-button form-field-wide" type="submit"><?= $isCreate ? 'ایجاد پروژه' : 'ذخیره تغییرات' ?></button>
          </form>
        </section>

        <aside class="portfolio-editor-side">
          <section class="panel image-preview-card"><div class="panel-heading"><div><p>پیش‌نمایش</p><h2>تصویر فعلی</h2></div></div><div class="admin-image-preview"><?php if (is_string($project['image_path']) && $project['image_path'] !== ''): ?><img src="/<?= e($project['image_path']) ?>" alt="پیش‌نمایش تصویر <?= e((string) $project['title']) ?>"><?php else: ?><span>تصویری ثبت نشده است.</span><?php endif; ?></div></section>
          <?php if (!$isCreate): ?>
            <section class="panel action-card"><div class="panel-heading"><div><p>نمایش عمومی</p><h2><?= (int) $project['is_published'] === 1 ? 'لغو انتشار' : 'انتشار پروژه' ?></h2></div></div><form class="stack-form" method="post" action="/admin/portfolio/<?= e((string) $project['id']) ?>/publication"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><input type="hidden" name="is_published" value="<?= (int) $project['is_published'] === 1 ? '0' : '1' ?>"><button class="secondary-button" type="submit"><?= (int) $project['is_published'] === 1 ? 'تبدیل به پیش‌نویس' : 'انتشار در وب‌سایت' ?></button></form></section>
            <details class="danger-zone panel"><summary>حذف پروژه</summary><div><p>پروژه از دیتابیس حذف می‌شود و فقط تصویر امنِ مدیریت‌شده توسط CMS پاک خواهد شد.</p><form class="stack-form" method="post" action="/admin/portfolio/<?= e((string) $project['id']) ?>/delete"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>"><label class="confirm-check"><input type="checkbox" name="confirm_delete" value="yes" required> حذف دائمی پروژه را تأیید می‌کنم.</label><button class="danger-button" type="submit">حذف دائمی</button></form></div></details>
          <?php endif; ?>
        </aside>
      </div>
    </main>
  </div>
</body>
</html>
