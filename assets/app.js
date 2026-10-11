const header = document.querySelector('[data-header]');
const menuButton = document.querySelector('[data-menu-toggle]');
const nav = document.querySelector('[data-nav]');
const portfolioGrid = document.querySelector('[data-portfolio-grid]');
const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

const closeMenu = () => {
  nav?.classList.remove('open');
  menuButton?.setAttribute('aria-expanded', 'false');
};

window.addEventListener('scroll', () => header?.classList.toggle('scrolled', window.scrollY > 24), { passive: true });

menuButton?.addEventListener('click', () => {
  const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
  menuButton.setAttribute('aria-expanded', String(!isOpen));
  nav?.classList.toggle('open', !isOpen);
});

nav?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));

document.addEventListener('keydown', event => {
  if (event.key === 'Escape' && nav?.classList.contains('open')) {
    closeMenu();
    menuButton?.focus();
  }
});

window.matchMedia('(min-width: 1024px)').addEventListener?.('change', event => event.matches && closeMenu());

const isTelegramUrl = value => {
  try {
    const url = new URL(value);
    return url.protocol === 'https:' &&
      url.hostname === 't.me' &&
      !url.username &&
      !url.password &&
      !url.port &&
      url.pathname.length > 1;
  } catch {
    return false;
  }
};

const createPortfolioCard = project => {
  const card = document.createElement('article');
  card.className = 'project-card reveal';
  card.dataset.category = project.category;
  card.dataset.projectId = project.id;

  const media = document.createElement('div');
  media.className = 'project-shot';
  const image = document.createElement('img');
  image.src = project.screenshot;
  image.alt = project.screenshotAlt;
  image.loading = 'lazy';
  image.decoding = 'async';
  media.append(image);

  if (project.status === 'placeholder') {
    const label = document.createElement('span');
    label.className = 'placeholder-label';
    label.textContent = 'نمونه آزمایشی طراحی';
    media.append(label);
  }

  const info = document.createElement('div');
  info.className = 'project-info';
  const category = document.createElement('span');
  category.className = 'project-category';
  category.textContent = project.categoryLabel;
  const title = document.createElement('h3');
  title.textContent = project.title;
  const description = document.createElement('p');
  description.textContent = project.description;
  info.append(category, title, description);

  const hasTelegramUrl = isTelegramUrl(project.telegramUrl);
  const action = hasTelegramUrl ? document.createElement('a') : document.createElement('span');
  action.className = hasTelegramUrl ? 'project-link' : 'project-link is-disabled';
  action.textContent = hasTelegramUrl ? 'مشاهده کامل در تلگرام ←' : 'مشاهده کامل در تلگرام — به‌زودی';
  if (action instanceof HTMLAnchorElement) {
    action.href = project.telegramUrl;
    action.target = '_blank';
    action.rel = 'noopener noreferrer';
    action.setAttribute('aria-label', `مشاهده کامل ${project.title} در تلگرام`);
  } else {
    action.setAttribute('aria-disabled', 'true');
  }
  info.append(action);
  card.append(media, info);
  return card;
};

const portfolioItems = Array.isArray(window.ARA_PORTFOLIO) ? window.ARA_PORTFOLIO : [];
const renderPortfolio = () => {
  if (!portfolioGrid) return;
  const fragment = document.createDocumentFragment();
  portfolioItems.forEach(item => fragment.append(createPortfolioCard(item)));
  portfolioGrid.replaceChildren(fragment);
};

renderPortfolio();

const revealItems = [...document.querySelectorAll('.reveal')];
if ('IntersectionObserver' in window && !reduceMotionQuery.matches) {
  document.querySelectorAll('.service-grid, .benefit-grid, .portfolio-grid, .process-list').forEach(group => {
    [...group.children].forEach((item, index) => item.style.setProperty('--reveal-delay', `${Math.min(index, 4) * 55}ms`));
  });
  document.documentElement.classList.add('motion-ready');
  const revealObserver = new IntersectionObserver(entries => entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      revealObserver.unobserve(entry.target);
    }
  }), { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
  revealItems.forEach(item => revealObserver.observe(item));
}

const heroEnterItems = document.querySelectorAll('[data-hero-enter]');
if (heroEnterItems.length && !reduceMotionQuery.matches) {
  document.documentElement.classList.add('hero-motion-ready');
  requestAnimationFrame(() => requestAnimationFrame(() => document.documentElement.classList.add('hero-entered')));
}

const parallaxStage = document.querySelector('[data-parallax-stage]');
const finePointerQuery = window.matchMedia('(hover: hover) and (pointer: fine)');
if (parallaxStage && finePointerQuery.matches && !reduceMotionQuery.matches) {
  const layers = [...parallaxStage.querySelectorAll('[data-parallax-layer]')];
  let targetX = 0;
  let targetY = 0;
  let currentX = 0;
  let currentY = 0;
  let frameId = 0;

  const animate = () => {
    currentX += (targetX - currentX) * 0.1;
    currentY += (targetY - currentY) * 0.1;
    layers.forEach(layer => {
      const depth = Number(layer.dataset.depth || 1);
      layer.style.setProperty('--parallax-x', `${(currentX * depth).toFixed(2)}px`);
      layer.style.setProperty('--parallax-y', `${(currentY * depth).toFixed(2)}px`);
    });
    if (Math.abs(targetX - currentX) > 0.02 || Math.abs(targetY - currentY) > 0.02) frameId = requestAnimationFrame(animate);
    else frameId = 0;
  };

  const startAnimation = () => {
    if (!frameId) frameId = requestAnimationFrame(animate);
  };

  parallaxStage.addEventListener('pointermove', event => {
    const rect = parallaxStage.getBoundingClientRect();
    targetX = ((event.clientX - rect.left) / rect.width - 0.5) * 9;
    targetY = ((event.clientY - rect.top) / rect.height - 0.5) * 9;
    startAnimation();
  }, { passive: true });

  parallaxStage.addEventListener('pointerleave', () => {
    targetX = 0;
    targetY = 0;
    startAnimation();
  });
}

const form = document.querySelector('[data-consultation-form]');
form?.addEventListener('submit', event => {
  event.preventDefault();
  const data = new FormData(form);
  const status = document.querySelector('[data-form-status]');
  const name = String(data.get('name') || '').trim();
  const contact = String(data.get('contact') || '').trim();
  const message = String(data.get('message') || '').trim();
  if (!name || !contact || !message) {
    status.textContent = 'لطفاً همه فیلدهای ضروری را تکمیل کنید.';
    return;
  }
  status.textContent = 'فرم در همین مرورگر بررسی شد؛ هنوز هیچ داده‌ای ارسال یا ذخیره نشده است.';
});

const year = document.querySelector('[data-year]');
if (year) year.textContent = new Intl.NumberFormat('fa-IR', { useGrouping: false }).format(new Date().getFullYear());
