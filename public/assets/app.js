const header = document.querySelector('[data-header]');
const menuButton = document.querySelector('[data-menu-toggle]');
const nav = document.querySelector('[data-nav]');
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
form?.addEventListener('submit', async event => {
  event.preventDefault();
  if (!form.reportValidity()) return;

  const data = new FormData(form);
  const status = form.querySelector('[data-form-status]');
  const submitButton = form.querySelector('[data-submit-button]');
  const csrfToken = form.querySelector('[data-csrf-token]')?.value || '';
  const payload = {
    name: String(data.get('name') || '').trim(),
    contact: String(data.get('contact') || '').trim(),
    service: String(data.get('service') || '').trim(),
    message: String(data.get('message') || '').trim(),
    website: String(data.get('website') || '').trim()
  };
  const originalButtonContent = submitButton?.innerHTML;

  if (status) {
    status.dataset.state = 'loading';
    status.textContent = 'در حال ثبت درخواست…';
  }
  form.setAttribute('aria-busy', 'true');
  if (submitButton) {
    submitButton.disabled = true;
    submitButton.textContent = 'در حال ارسال…';
  }

  try {
    const response = await fetch(form.action, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
      },
      body: JSON.stringify(payload)
    });
    const result = await response.json().catch(() => null);

    if (!response.ok || result?.ok !== true) {
      const firstError = result?.errors && Object.values(result.errors)[0];
      const submissionError = new Error(firstError || result?.message || 'ثبت درخواست انجام نشد. لطفاً دوباره تلاش کنید.');
      submissionError.name = 'ConsultationSubmissionError';
      throw submissionError;
    }

    form.reset();
    if (status) {
      status.dataset.state = 'success';
      status.textContent = result.message || 'درخواست مشاوره با موفقیت ثبت شد.';
    }
  } catch (error) {
    if (status) {
      status.dataset.state = 'error';
      status.textContent = error instanceof Error && error.name === 'ConsultationSubmissionError'
        ? error.message
        : 'ارتباط با سرور برقرار نشد. اطلاعات فرم حفظ شده است؛ دوباره تلاش کنید.';
    }
  } finally {
    form.removeAttribute('aria-busy');
    if (submitButton) {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonContent || 'ارسال درخواست مشاوره';
    }
  }
});

const year = document.querySelector('[data-year]');
if (year) year.textContent = new Intl.NumberFormat('fa-IR', { useGrouping: false }).format(new Date().getFullYear());
