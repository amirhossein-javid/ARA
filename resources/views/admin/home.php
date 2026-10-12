<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>مدیریت | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
  <main class="admin-layout">
    <section class="auth-card admin-home" aria-labelledby="admin-title">
      <div class="admin-topbar">
        <a class="admin-brand" href="/" aria-label="وب‌سایت ARA Studio">
          <img src="/assets/ara-logo.png" alt="">
          <span><b>ARA</b><small>STUDIO</small></span>
        </a>
        <form method="post" action="/admin/logout">
          <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
          <button class="logout-button" type="submit">خروج امن</button>
        </form>
      </div>
      <div class="admin-confirmation">
        <span class="status-mark" aria-hidden="true">✓</span>
        <p>ورود موفق</p>
        <h1 id="admin-title">خوش آمدید، <bdi><?= e($adminUsername) ?></bdi></h1>
        <div class="phase-note">
          <strong>احراز هویت مدیریت فعال است.</strong>
          <span>امکانات داشبورد در فاز بعدی اضافه خواهند شد.</span>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
