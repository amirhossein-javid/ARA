<!doctype html>
<html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>مدیر جدید | ARA Studio</title><link rel="stylesheet" href="/assets/admin.css"></head>
<body class="dashboard-body"><div class="dashboard-shell">
<?php require __DIR__ . '/_navigation.php'; ?>
<main class="admin-content"><div class="page-heading detail-heading"><div><a class="eyebrow-link" href="/admin/admins">← بازگشت به مدیران</a><h1>ایجاد حساب مدیر</h1><span>حساب جدید همان سطح دسترسی سایر مدیران را خواهد داشت.</span></div></div>
<?php if ($error !== null): ?><p class="auth-alert" role="alert"><?= e($error) ?></p><?php endif; ?>
<section class="panel account-form-panel"><form class="portfolio-form account-form" method="post" action="/admin/admins/create" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
<div class="form-field-wide"><label for="username">نام کاربری</label><input id="username" name="username" value="<?= e($username) ?>" dir="ltr" minlength="3" maxlength="64" pattern="[a-z0-9][a-z0-9._-]{2,63}" autocomplete="off" required><small>فقط حروف کوچک انگلیسی، عدد، نقطه، خط تیره و زیرخط.</small></div>
<div><label for="new-password">رمز عبور جدید</label><input id="new-password" name="new_password" type="password" minlength="14" maxlength="4096" autocomplete="new-password" required></div>
<div><label for="confirm-password">تکرار رمز عبور</label><input id="confirm-password" name="new_password_confirmation" type="password" minlength="14" maxlength="4096" autocomplete="new-password" required></div>
<div class="form-field-wide sensitive-confirm"><label for="current-password">رمز عبور فعلی شما</label><input id="current-password" name="current_password" type="password" maxlength="4096" autocomplete="current-password" required><small>برای تأیید ایجاد حساب جدید لازم است.</small></div>
<div class="form-field-wide form-actions"><button class="primary-button" type="submit">ایجاد حساب امن</button><a class="secondary-button" href="/admin/admins">انصراف</a></div>
</form></section></main></div></body></html>
