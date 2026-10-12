<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($seoSettings['meta_description']) ?>">
  <meta name="robots" content="<?= e($robotsDirective) ?>">
  <meta name="theme-color" content="#0b0c0e">
  <title><?= e($seoSettings['homepage_title']) ?></title>
  <link rel="canonical" href="<?= e($seoSettings['canonical_url']) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="fa_IR">
  <meta property="og:site_name" content="<?= e($siteSettings['brand_name']) ?>">
  <meta property="og:title" content="<?= e($seoSettings['og_title']) ?>">
  <meta property="og:description" content="<?= e($seoSettings['og_description']) ?>">
  <meta property="og:url" content="<?= e($seoSettings['canonical_url']) ?>">
  <meta property="og:image" content="<?= e($socialImageUrl) ?>">
  <meta property="og:image:alt" content="<?= e($siteSettings['brand_name']) ?>">
  <meta name="twitter:card" content="<?= e($seoSettings['twitter_card']) ?>">
  <meta name="twitter:title" content="<?= e($seoSettings['twitter_title']) ?>">
  <meta name="twitter:description" content="<?= e($seoSettings['twitter_description']) ?>">
  <meta name="twitter:image" content="<?= e($socialImageUrl) ?>">
  <?php if ($seoSettings['google_verification'] !== ''): ?><meta name="google-site-verification" content="<?= e($seoSettings['google_verification']) ?>"><?php endif; ?>
  <?php if ($seoSettings['bing_verification'] !== ''): ?><meta name="msvalidate.01" content="<?= e($seoSettings['bing_verification']) ?>"><?php endif; ?>
  <link rel="icon" href="assets/ara-logo.png" type="image/png">
  <link rel="preload" href="assets/fonts/Vazirmatn-wght.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="assets/styles.css">
  <script src="assets/app.js" defer></script>
  <script type="application/ld+json" nonce="<?= e($cspNonce) ?>"><?= $structuredDataJson ?></script>
</head>
<body>
  <a class="skip-link" href="#main">رفتن به محتوای اصلی</a>

  <header class="site-header" data-header>
    <div class="container header-inner">
      <a class="brand" href="#top" aria-label="صفحه اصلی <?= e($siteSettings['brand_name']) ?>"><img src="assets/ara-logo.png" alt="لوگوی <?= e($siteSettings['brand_name']) ?>"><span><b>ARA</b><small>STUDIO</small></span></a>
      <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-menu-toggle><span></span><span></span><span></span><span class="sr-only">باز کردن منو</span></button>
      <nav id="main-nav" class="main-nav" aria-label="منوی اصلی" data-nav><a href="#services">خدمات</a><a href="#difference">چرا ARA؟</a><a href="#work">نمونه‌کارها</a><a href="#process">مسیر همکاری</a></nav>
      <a class="button button-dark header-cta" href="#consultation">درخواست مشاوره <span aria-hidden="true">←</span></a>
    </div>
  </header>

  <main id="main">
    <section class="hero" id="top">
      <div class="hero-grid-pattern" aria-hidden="true"></div>
      <div class="container hero-layout">
        <div class="hero-copy hero-enter" data-hero-enter>
          <div class="eyebrow"><i></i><span>استودیو طراحی و تجربه دیجیتال</span></div>
          <h1>ایده‌ها را به تجربه‌های دیجیتال <span>متمایز</span> تبدیل می‌کنیم.</h1>
          <p>با ترکیب نگاه استراتژیک، طراحی دقیق و اجرای فنی، وب‌سایت‌هایی می‌سازیم که هویت برند را واضح منتقل می‌کنند و مسیر ارتباط مخاطب با کسب‌وکار را ساده‌تر می‌سازند.</p>
          <div class="hero-actions"><a class="button button-dark" href="#consultation">درخواست مشاوره <span aria-hidden="true">←</span></a><a class="button button-ghost" href="#work">مشاهده نمونه‌کارها <span aria-hidden="true">↙</span></a></div>
          <div class="hero-tags" aria-label="زمینه‌های تخصصی"><span>طراحی وب‌سایت</span><span>UI / UX</span><span>هویت بصری</span></div>
        </div>
        <div class="hero-showcase hero-enter" data-hero-enter data-parallax-stage aria-label="نمایش طراحی واکنش‌گرای وب‌سایت در دسکتاپ و موبایل">
          <div class="showcase-orbit" aria-hidden="true"></div>
          <div class="showcase-glow" data-parallax-layer data-depth="0.3"></div>
          <div class="desktop-frame" data-parallax-layer data-depth="0.75"><div class="browser-bar"><div><i></i><i></i><i></i></div><span><?= e($siteWebsiteHost) ?> / experience</span></div><div class="desktop-screen"><div class="screen-nav"><b>ARA.</b><div><span></span><span></span><span></span></div><em>شروع پروژه</em></div><div class="screen-hero"><div class="screen-copy"><small>DESIGN / STRATEGY / TECHNOLOGY</small><strong>Distinct digital<br><mark>experiences.</mark></strong><i></i><i></i><button type="button" tabindex="-1">Explore work →</button></div><div class="screen-art"><div class="chrome-orb"><span></span></div><div class="screen-card"><small>PROJECT FLOW</small><b>Clear by design</b><i></i></div></div></div><div class="screen-stats"><span><b>01</b><small>Discover</small></span><span><b>02</b><small>Design</small></span><span><b>03</b><small>Deliver</small></span></div></div></div>
          <div class="mobile-frame" data-parallax-layer data-depth="1.15"><div class="mobile-notch"></div><div class="mobile-screen"><div class="mobile-nav"><b>A</b><i></i></div><small>BUILT FOR CLARITY</small><strong>Digital<br><mark>clarity.</mark></strong><div class="mobile-visual"><div></div><span></span></div><span class="mobile-button"></span></div></div>
          <div class="floating-note note-top" data-parallax-layer data-depth="1.45"><span>✓</span><div><b>طراحی واکنش‌گرا</b><small>از موبایل تا دسکتاپ</small></div></div>
          <div class="floating-note note-bottom" data-parallax-layer data-depth="1.7"><span>↗</span><div><b>مسیر روشن پروژه</b><small>از شناخت تا اجرا</small></div></div>
          <div class="floating-metric" data-parallax-layer data-depth="1.25"><small>INTERACTION</small><b>Purposeful motion</b><i></i></div>
        </div>
      </div>
      <div class="container hero-proof"><p>راه‌حل مناسب، از شناخت درست مسئله شروع می‌شود.</p><a href="#process">آشنایی با مسیر همکاری <span>←</span></a></div>
    </section>

    <section class="section services" id="services" aria-labelledby="services-title">
      <div class="container">
        <div class="section-heading reveal"><div><p class="kicker">خدمات ARA</p><h2 id="services-title">برای هر نیاز، یک مسیر روشن طراحی می‌کنیم.</h2></div><p>خدمات به‌صورت ثابت و بسته‌بندی‌شده ارائه نمی‌شوند؛ دامنه هر پروژه بعد از شناخت هدف، مخاطب و محدودیت‌های واقعی مشخص خواهد شد.</p></div>
        <div class="service-grid">
          <article class="service-card reveal"><div class="service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 9v11"/></svg></div><span class="card-number">01</span><h3>وب‌سایت شرکتی</h3><p>معرفی حرفه‌ای برند، خدمات و مزیت‌ها با ساختاری روشن و قابل اعتماد.</p><a href="#consultation">مشاوره وب‌سایت شرکتی <span>←</span></a></article>
          <article class="service-card reveal"><div class="service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h2l2 10h9l2-7H7"/><circle cx="10" cy="19" r="1"/><circle cx="17" cy="19" r="1"/></svg></div><span class="card-number">02</span><h3>وب‌سایت فروشگاهی</h3><p>طراحی مسیر خرید ساده، نمایش منظم محصول و تجربه‌ای سازگار با موبایل.</p><a href="#consultation">مشاوره فروشگاه آنلاین <span>←</span></a></article>
          <article class="service-card reveal"><div class="service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h5M8 16h3"/></svg></div><span class="card-number">03</span><h3>لندینگ‌پیج</h3><p>صفحه‌ای متمرکز برای کمپین، معرفی محصول یا هدایت مخاطب به یک اقدام مشخص.</p><a href="#consultation">مشاوره لندینگ‌پیج <span>←</span></a></article>
          <article class="service-card reveal"><div class="service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 7v10l8 4 8-4V7zM4 7l8 4 8-4M12 11v10"/></svg></div><span class="card-number">04</span><h3>برندینگ و هویت بصری</h3><p>ساخت زبان بصری منسجم برای حضوری متمایز در نقاط تماس مختلف برند.</p><a href="#consultation">مشاوره هویت برند <span>←</span></a></article>
          <article class="service-card reveal"><div class="service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM8 10h3v4H8zM14 9h2M14 12h3M14 15h2"/></svg></div><span class="card-number">05</span><h3>طراحی UI / UX</h3><p>تبدیل جریان‌های پیچیده به رابط‌هایی قابل فهم، منظم و خوش‌استفاده.</p><a href="#consultation">مشاوره تجربه کاربری <span>←</span></a></article>
          <article class="service-card service-card-dark reveal"><div class="service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.2 5.8L20 11l-5.8 2.2L12 19l-2.2-5.8L4 11l5.8-2.2z"/></svg></div><span class="card-number">06</span><h3>راهکار دیجیتال اختصاصی</h3><p>برای مسئله‌هایی که به ترکیبی از طراحی، تعامل و پیاده‌سازی ویژه نیاز دارند.</p><a href="#consultation">گفت‌وگو درباره پروژه <span>←</span></a></article>
        </div>
      </div>
    </section>

    <section class="section difference" id="difference" aria-labelledby="difference-title">
      <div class="container">
        <div class="section-heading reveal"><div><p class="kicker">چرا ARA؟</p><h2 id="difference-title">جزئیاتی که کیفیت همکاری را می‌سازند.</h2></div><p>به‌جای ادعاهای عددی، روی اصولی تمرکز می‌کنیم که در تصمیم‌های روزمره پروژه قابل مشاهده‌اند.</p></div>
        <div class="benefit-grid">
          <article class="benefit benefit-wide reveal"><div class="benefit-content"><span>01</span><h3>طراحی اختصاصی برای مسئله واقعی</h3><p>ساختار محتوا، مسیر کاربر و زبان بصری بر اساس نیاز همان پروژه شکل می‌گیرد؛ نه بر پایه یک قالب تکراری.</p></div><div class="benefit-visual visual-system" aria-hidden="true"><div class="system-window"><i></i><i></i><i></i><span></span><span></span><b></b></div><div class="system-chip">ARA / DESIGN SYSTEM</div></div></article>
          <article class="benefit reveal"><span>02</span><div class="benefit-icon">↔</div><h3>ارتباط روشن</h3><p>تصمیم‌ها و بازخوردها در نقاط مشخص جمع‌بندی می‌شوند تا مسیر پروژه قابل پیگیری بماند.</p></article>
          <article class="benefit reveal"><span>03</span><div class="benefit-icon">⌁</div><h3>برنامه‌ریزی مشارکتی</h3><p>هدف، اولویت‌ها و محدودیت‌ها را با هم روشن می‌کنیم تا برنامه اجرا واقع‌بینانه باشد.</p></article>
          <article class="benefit benefit-tall reveal"><span>04</span><h3>اجرای واکنش‌گرا</h3><p>رابط از ابتدا برای اندازه‌های مختلف طراحی می‌شود تا تجربه در موبایل و دسکتاپ منسجم بماند.</p><div class="device-stack" aria-hidden="true"><div class="device desktop-device"><i></i><b></b><b></b></div><div class="device phone-device"><i></i><b></b><b></b></div></div></article>
          <article class="benefit reveal"><span>05</span><div class="benefit-icon">◎</div><h3>تمرکز بر استفاده‌پذیری</h3><p>سلسله‌مراتب، خوانایی و مسیر اقدام کاربر در کنار کیفیت بصری سنجیده می‌شوند.</p></article>
          <article class="benefit benefit-accent reveal"><span>06</span><div><h3>توسعه تمیز و سبک</h3><p>پیاده‌سازی با ساختاری قابل نگهداری، وابستگی کم و سازگار با میزبانی اشتراکی انجام می‌شود.</p></div><div class="code-lines" aria-hidden="true"><i></i><i></i><i></i><i></i></div></article>
        </div>
      </div>
    </section>

    <section class="section portfolio" id="work" aria-labelledby="work-title">
      <div class="container">
        <div class="portfolio-heading reveal"><p class="kicker">منتخب پروژه‌ها</p><h2 id="work-title">نمونه‌کارها</h2><p><?= $portfolioIsTemporary ? 'این شش کارت صرفاً نمونه‌های آزمایشی هستند و به مشتری یا همکاری واقعی اشاره ندارند.' : 'مروری بر پروژه‌هایی که برای نمایش عمومی انتخاب شده‌اند.' ?></p></div>
        <?php if ($portfolioIsTemporary): ?><div class="portfolio-note reveal"><span>۶ نمونه آزمایشی</span><p>این محتوا موقت است و با ثبت اولین پروژه واقعی در پنل مدیریت کنار می‌رود.</p></div><?php endif; ?>
        <div class="portfolio-grid" data-portfolio-grid aria-live="polite">
          <?php if ($portfolioProjects === []): ?><p class="portfolio-empty reveal">در حال حاضر نمونه‌کاری برای نمایش عمومی منتشر نشده است.</p><?php endif; ?>
          <?php foreach ($portfolioProjects as $project): ?>
            <?php $isPlaceholder = (bool) ($project['is_placeholder'] ?? false); ?>
            <article class="project-card reveal">
              <div class="project-shot">
                <?php if (is_string($project['image_path'] ?? null) && $project['image_path'] !== ''): ?>
                  <img src="/<?= e($project['image_path']) ?>" alt="پیش‌نمایش <?= e((string) $project['title']) ?>" loading="lazy" decoding="async">
                <?php else: ?>
                  <div class="project-image-placeholder" aria-hidden="true"><span>ARA</span></div>
                <?php endif; ?>
                <?php if ($isPlaceholder): ?><span class="placeholder-label">نمونه آزمایشی طراحی</span><?php endif; ?>
              </div>
              <div class="project-info">
                <span class="project-category"><?= e((string) ($project['label'] ?? 'پروژه ARA')) ?></span>
                <h3><?= e((string) $project['title']) ?></h3>
                <p><?= nl2br(e((string) $project['short_description'])) ?></p>
                <?php if (is_string($project['telegram_url'] ?? null) && $project['telegram_url'] !== ''): ?>
                  <a class="project-link" href="<?= e($project['telegram_url']) ?>" target="_blank" rel="noopener noreferrer">مشاهده بیشتر <span aria-hidden="true">←</span></a>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section process" id="process" aria-labelledby="process-title">
      <div class="container">
        <div class="section-heading reveal"><div><p class="kicker">مسیر همکاری</p><h2 id="process-title">از شناخت مسئله تا تحویل یک تجربه منسجم.</h2></div><p>مراحل متناسب با پروژه تنظیم می‌شوند، اما منطق تصمیم‌گیری روشن و قابل دنبال‌کردن باقی می‌ماند.</p></div>
        <ol class="process-list"><li class="reveal"><span>01</span><div class="process-icon">⌕</div><h3>شناخت و مشاوره</h3><p>هدف، مخاطب، محدودیت و وضعیت فعلی پروژه را مرور می‌کنیم.</p></li><li class="reveal"><span>02</span><div class="process-icon">⌁</div><h3>تعریف مسیر</h3><p>دامنه همکاری، اولویت‌ها و خروجی‌های موردنیاز روشن می‌شوند.</p></li><li class="reveal"><span>03</span><div class="process-icon">✦</div><h3>طراحی و بازبینی</h3><p>راه‌حل مرحله‌به‌مرحله طراحی و بازخوردها در نقاط مشخص جمع‌بندی می‌شوند.</p></li><li class="reveal"><span>04</span><div class="process-icon">✓</div><h3>اجرا و تحویل</h3><p>خروجی توافق‌شده آماده می‌شود و مسیر ادامه کار مشخص خواهد شد.</p></li></ol>
        <div class="process-cta reveal"><div><b>نقطه شروع را نمی‌دانید؟</b><p>در گفت‌وگوی اولیه، مناسب‌ترین مسیر را با هم پیدا می‌کنیم.</p></div><a class="button button-dark" href="#consultation">مشاوره پروژه <span>←</span></a></div>
      </div>
    </section>

    <section class="consultation" id="consultation" aria-labelledby="consultation-title">
      <div class="container consultation-shell">
        <div class="consultation-copy reveal"><p class="kicker">شروع همکاری</p><h2 id="consultation-title">درباره پروژه‌تان<br><span>چند خط بنویسید.</span></h2><p>فرم را تکمیل کنید تا درخواست شما با اطلاعات اولیه پروژه برای بررسی ثبت شود.</p><ul><li><span>✓</span> بدون نیاز به انتخاب پکیج یا قیمت ثابت</li><li><span>✓</span> مناسب حتی وقتی دامنه پروژه هنوز دقیق نیست</li></ul></div>
        <form class="consultation-form reveal" action="/api/consultation" method="post" data-consultation-form><input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>" data-csrf-token><div class="honeypot" aria-hidden="true"><label for="website">وب‌سایت</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div><div class="field"><label for="name">نام و نام خانوادگی</label><input id="name" name="name" autocomplete="name" minlength="2" maxlength="100" required placeholder="نام شما"></div><div class="field"><label for="contact">راه ارتباطی</label><input id="contact" name="contact" autocomplete="email" inputmode="email" maxlength="190" required placeholder="شماره تماس یا ایمیل"></div><div class="field field-full"><label for="service">موضوع همکاری</label><select id="service" name="service" required><option value="نیاز به راهنمایی دارم">نیاز به راهنمایی دارم</option><option value="وب‌سایت شرکتی">وب‌سایت شرکتی</option><option value="وب‌سایت فروشگاهی">وب‌سایت فروشگاهی</option><option value="لندینگ‌پیج">لندینگ‌پیج</option><option value="برندینگ و هویت بصری">برندینگ و هویت بصری</option><option value="طراحی UI / UX">طراحی UI / UX</option><option value="راهکار دیجیتال اختصاصی">راهکار دیجیتال اختصاصی</option></select></div><div class="field field-full"><label for="message">درباره پروژه</label><textarea id="message" name="message" rows="4" minlength="10" maxlength="2000" required placeholder="هدف، نیاز یا مسئله اصلی را کوتاه توضیح دهید"></textarea></div><button class="button button-dark field-full" type="submit" data-submit-button>ارسال درخواست مشاوره <span aria-hidden="true">←</span></button><p class="form-status field-full" role="status" aria-live="polite" aria-atomic="true" data-form-status></p></form>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-main"><a class="brand footer-brand" href="#top" aria-label="<?= e($siteSettings['brand_name']) ?>"><img src="assets/ara-logo.png" alt=""><span><b>ARA</b><small>STUDIO</small></span></a><div><p>طراحی هویت و تجربه‌های دیجیتال با تمرکز بر وضوح، انسجام و عملکرد.</p><a href="#consultation">درخواست مشاوره <span>←</span></a></div><nav aria-label="پیوندهای پایین صفحه"><a href="#services">خدمات</a><a href="#difference">چرا ARA؟</a><a href="#work">نمونه‌کارها</a><a href="#process">مسیر همکاری</a></nav></div>
    <div class="container footer-bottom"><span>© <span data-year></span> <?= e($siteSettings['brand_name']) ?></span><div class="footer-contact"><a href="<?= e($siteSettings['website_url']) ?>"><?= e($siteWebsiteHost) ?></a><a href="mailto:<?= e($siteSettings['contact_email']) ?>"><?= e($siteSettings['contact_email']) ?></a><a href="<?= e($siteSettings['telegram_url']) ?>" target="_blank" rel="noopener noreferrer">Telegram</a><?php if ($siteTelephoneHref !== null): ?><a href="<?= e($siteTelephoneHref) ?>"><bdi><?= e($siteSettings['contact_phone']) ?></bdi></a><?php endif; ?></div><a href="#top">بازگشت به بالا ↑</a></div>
  </footer>
</body>
</html>
