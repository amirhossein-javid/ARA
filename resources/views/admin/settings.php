<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>تنظیمات سایت | ARA Studio</title><link rel="stylesheet" href="/assets/admin.css"></head>
<body class="dashboard-body"><div class="dashboard-shell">
<?php require __DIR__ . '/_navigation.php'; ?>
<main class="admin-content"><div class="page-heading"><div><p>محتوای عمومی</p><h1>تنظیمات سایت</h1><span>فقط اطلاعات عمومی از پیش تعریف‌شده قابل ویرایش است؛ هیچ تنظیم اجرایی یا محرمانه‌ای در این بخش وجود ندارد.</span></div></div>
<?php if ($error !== null): ?><p class="auth-alert" role="alert"><?= e($error) ?></p><?php endif; ?><?php if ($notice === 'saved'): ?><p class="success-notice" role="status">تنظیمات عمومی ذخیره شد.</p><?php endif; ?>
<section class="panel settings-panel"><form class="portfolio-form settings-form" method="post" action="/admin/settings"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
<div class="form-field-wide"><label for="brand-name">نام برند</label><input id="brand-name" name="brand_name" value="<?= e($settings['brand_name']) ?>" maxlength="80" required></div>
<div><label for="website-url">وب‌سایت</label><input id="website-url" name="website_url" type="url" dir="ltr" value="<?= e($settings['website_url']) ?>" maxlength="255" pattern="https://.*" required></div>
<div><label for="telegram-url">تلگرام</label><input id="telegram-url" name="telegram_url" type="url" dir="ltr" value="<?= e($settings['telegram_url']) ?>" maxlength="255" pattern="https://t\.me/[A-Za-z0-9_]{5,32}/?" required></div>
<div><label for="contact-email">ایمیل تماس</label><input id="contact-email" name="contact_email" type="email" dir="ltr" value="<?= e($settings['contact_email']) ?>" maxlength="190" required></div>
<div><label for="contact-phone">شماره تماس عمومی <small>(اختیاری)</small></label><input id="contact-phone" name="contact_phone" type="tel" dir="ltr" value="<?= e($settings['contact_phone']) ?>" maxlength="32" placeholder="+98 ..."></div>
<div class="form-field-wide form-actions"><button class="primary-button" type="submit">ذخیره تنظیمات</button><a class="secondary-button" href="/" target="_blank" rel="noopener">مشاهده سایت</a></div>
</form></section></main></div></body></html>
