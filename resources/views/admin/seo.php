<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>مدیریت سئو | ARA Studio</title><link rel="stylesheet" href="/assets/admin.css"></head>
<body class="dashboard-body"><div class="dashboard-shell">
<?php require __DIR__ . '/_navigation.php'; ?>
<main class="admin-content"><div class="page-heading"><div><p>نمایش در جست‌وجو و شبکه‌های اجتماعی</p><h1>مدیریت سئو</h1><span>متادیتای صفحه اصلی، دسترسی ربات‌ها و تصویر پیش‌فرض اشتراک‌گذاری را مدیریت کنید.</span></div></div>
<?php if ($error !== null): ?><p class="auth-alert" role="alert"><?= e($error) ?></p><?php endif; ?><?php if ($notice === 'saved'): ?><p class="success-notice" role="status">تنظیمات سئو با موفقیت ذخیره شد.</p><?php endif; ?>
<form class="seo-admin-form" method="post" action="/admin/seo" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
  <section class="panel seo-section"><div class="panel-heading"><div><p>صفحه اصلی</p><h2>متادیتای پایه</h2></div></div><div class="portfolio-form settings-form">
    <div class="form-field-wide"><label for="homepage-title">عنوان SEO</label><input id="homepage-title" name="homepage_title" value="<?= e($settings['homepage_title']) ?>" minlength="10" maxlength="70" required><small>۱۰ تا ۷۰ نویسه؛ عنوان مرورگر و نتیجه جست‌وجو.</small></div>
    <div class="form-field-wide"><label for="meta-description">توضیحات متا</label><textarea id="meta-description" name="meta_description" rows="3" minlength="30" maxlength="170" required><?= e($settings['meta_description']) ?></textarea><small>۳۰ تا ۱۷۰ نویسه و بدون ادعای تأییدنشده.</small></div>
    <div class="form-field-wide"><label for="canonical-url">نشانی Canonical</label><input id="canonical-url" name="canonical_url" type="url" dir="ltr" value="<?= e($settings['canonical_url']) ?>" maxlength="255" pattern="https://.*" required><small>باید روی دامنه عمومی سایت و بدون query یا fragment باشد.</small></div>
  </div></section>

  <section class="panel seo-section"><div class="panel-heading"><div><p>اشتراک‌گذاری</p><h2>Open Graph و Twitter/X</h2></div></div><div class="portfolio-form settings-form">
    <div><label for="og-title">عنوان Open Graph</label><input id="og-title" name="og_title" value="<?= e($settings['og_title']) ?>" minlength="10" maxlength="95" required></div>
    <div><label for="twitter-title">عنوان Twitter/X</label><input id="twitter-title" name="twitter_title" value="<?= e($settings['twitter_title']) ?>" minlength="10" maxlength="70" required></div>
    <div><label for="og-description">توضیحات Open Graph</label><textarea id="og-description" name="og_description" rows="3" minlength="20" maxlength="200" required><?= e($settings['og_description']) ?></textarea></div>
    <div><label for="twitter-description">توضیحات Twitter/X</label><textarea id="twitter-description" name="twitter_description" rows="3" minlength="20" maxlength="200" required><?= e($settings['twitter_description']) ?></textarea></div>
    <div class="form-field-wide"><label for="twitter-card">نوع کارت Twitter/X</label><select id="twitter-card" name="twitter_card" required><option value="summary_large_image"<?= $settings['twitter_card'] === 'summary_large_image' ? ' selected' : '' ?>>تصویر بزرگ</option><option value="summary"<?= $settings['twitter_card'] === 'summary' ? ' selected' : '' ?>>خلاصه</option></select></div>
    <div class="form-field-wide upload-field"><label for="seo-image">تصویر پیش‌فرض Open Graph و شبکه‌های اجتماعی</label><input id="seo-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPEG، PNG یا WebP؛ حداکثر ۵ مگابایت. اندازه پیشنهادی ۱۲۰۰×۶۳۰ پیکسل است.</small></div>
    <div class="form-field-wide seo-image-preview"><?php if ($settings['social_image_path'] !== ''): ?><img src="/<?= e($settings['social_image_path']) ?>" alt="پیش‌نمایش تصویر اشتراک‌گذاری"><?php endif; ?><label class="confirm-check"><input type="checkbox" name="remove_image" value="yes"><span>بازنشانی تصویر به لوگوی اصلی ARA</span></label></div>
  </div></section>

  <section class="panel seo-section"><div class="panel-heading"><div><p>خزش و مالکیت</p><h2>Robots و تأیید سایت</h2></div></div><div class="portfolio-form settings-form">
    <div class="form-field-wide seo-toggle-grid"><label class="confirm-check"><input type="hidden" name="robots_index" value="0"><input type="checkbox" name="robots_index" value="1"<?= $settings['robots_index'] === '1' ? ' checked' : '' ?>><span>اجازه index شدن صفحه اصلی</span></label><label class="confirm-check"><input type="hidden" name="robots_follow" value="0"><input type="checkbox" name="robots_follow" value="1"<?= $settings['robots_follow'] === '1' ? ' checked' : '' ?>><span>اجازه دنبال‌کردن پیوندهای صفحه</span></label></div>
    <div><label for="google-verification">Google Search Console <small>(اختیاری)</small></label><input id="google-verification" name="google_verification" dir="ltr" value="<?= e($settings['google_verification']) ?>" maxlength="255" autocomplete="off"><small>فقط مقدار content توکن، نه کل تگ HTML.</small></div>
    <div><label for="bing-verification">Bing Webmaster Tools <small>(اختیاری)</small></label><input id="bing-verification" name="bing_verification" dir="ltr" value="<?= e($settings['bing_verification']) ?>" maxlength="255" autocomplete="off"><small>فقط مقدار content توکن، نه کل تگ HTML.</small></div>
  </div></section>
  <div class="seo-submit-bar"><button class="primary-button" type="submit">ذخیره تنظیمات سئو</button><a class="secondary-button" href="/" target="_blank" rel="noopener">مشاهده صفحه اصلی</a></div>
</form></main></div></body></html>
