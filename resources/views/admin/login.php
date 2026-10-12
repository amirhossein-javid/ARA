<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>ورود مدیران | ARA Studio</title>
  <link rel="stylesheet" href="/assets/admin.css">
</head>
<body>
  <main class="admin-layout">
    <section class="auth-card" aria-labelledby="login-title">
      <a class="admin-brand" href="/" aria-label="بازگشت به وب‌سایت ARA Studio">
        <img src="/assets/ara-logo.png" alt="">
        <span><b>ARA</b><small>STUDIO</small></span>
      </a>
      <div class="auth-heading">
        <p>پنل مدیریت</p>
        <h1 id="login-title">ورود مدیران</h1>
        <span>برای ادامه، اطلاعات حساب مدیریت را وارد کنید.</span>
      </div>
      <?php if ($errorMessage !== null): ?>
        <p class="auth-alert" role="alert"><?= e($errorMessage) ?></p>
      <?php endif; ?>
      <form class="auth-form" method="post" action="/admin/login" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="auth-field">
          <label for="admin-username">نام کاربری</label>
          <input id="admin-username" name="username" type="text" value="<?= e($username) ?>" autocomplete="username" autocapitalize="none" spellcheck="false" minlength="3" maxlength="64" pattern="[a-z0-9][a-z0-9._-]{2,63}" required autofocus>
        </div>
        <div class="auth-field">
          <label for="admin-password">رمز عبور</label>
          <input id="admin-password" name="password" type="password" autocomplete="current-password" maxlength="4096" required>
        </div>
        <button class="auth-button" type="submit">ورود امن <span aria-hidden="true">←</span></button>
      </form>
      <a class="back-link" href="/">بازگشت به وب‌سایت</a>
    </section>
  </main>
</body>
</html>
