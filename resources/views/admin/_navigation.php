<header class="admin-header">
  <a class="admin-brand" href="/admin" aria-label="داشبورد ARA Studio">
    <img src="/assets/ara-logo.png" alt="">
    <span><b>ARA</b><small>ADMIN</small></span>
  </a>
  <div class="admin-account">
    <span>مدیر <bdi><?= e($adminUsername) ?></bdi></span>
    <form method="post" action="/admin/logout">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
      <button class="logout-button" type="submit">خروج امن</button>
    </form>
  </div>
</header>
<nav class="admin-sidebar" aria-label="ناوبری مدیریت">
  <div class="sidebar-heading">مدیریت استودیو</div>
  <a class="nav-item<?= $activeNavigation === 'dashboard' ? ' is-active' : '' ?>" href="/admin"><span aria-hidden="true">⌂</span><b>داشبورد</b></a>
  <a class="nav-item<?= $activeNavigation === 'consultations' ? ' is-active' : '' ?>" href="/admin/consultations"><span aria-hidden="true">◫</span><b>درخواست‌های مشاوره</b></a>
  <div class="sidebar-divider"></div>
  <a class="nav-item<?= $activeNavigation === 'portfolio' ? ' is-active' : '' ?>" href="/admin/portfolio"><span aria-hidden="true">◇</span><b>نمونه‌کارها</b></a>
  <span class="nav-item is-disabled" aria-disabled="true"><span aria-hidden="true">♙</span><b>مدیران</b><small>فاز آینده</small></span>
  <span class="nav-item is-disabled" aria-disabled="true"><span aria-hidden="true">◎</span><b>سئو</b><small>فاز آینده</small></span>
  <span class="nav-item is-disabled" aria-disabled="true"><span aria-hidden="true">⚙</span><b>تنظیمات</b><small>فاز آینده</small></span>
  <a class="site-link" href="/" target="_blank" rel="noopener">مشاهده وب‌سایت <span aria-hidden="true">↗</span></a>
</nav>
