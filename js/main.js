/* ============================================================
   BRICEÑO CANALES — main.js
   ============================================================ */

/* ── Navbar scroll ──────────────────────────────────────────── */
(function () {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;

  function onScroll() {
    if (window.scrollY > 80) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();


/* ── Scroll-reveal con IntersectionObserver ─────────────────── */
(function () {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReduced) return;

  const targets = document.querySelectorAll('.reveal');
  if (!targets.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  targets.forEach((el) => observer.observe(el));
})();


/* ── Hairlines de cobre animadas ────────────────────────────── */
(function () {
  const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const hairlines = document.querySelectorAll('.hairline-cobre');
  if (!hairlines.length) return;

  if (prefersReduced) {
    hairlines.forEach((el) => el.classList.add('drawn'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('drawn');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  hairlines.forEach((el) => observer.observe(el));
})();


/* ── Flip-cards ─────────────────────────────────────────────── */
(function () {
  const cards = document.querySelectorAll('.flip-card');
  if (!cards.length) return;

  /* detectar si el dispositivo no soporta hover real (touch) */
  const isTouch = window.matchMedia('(hover: none)').matches;

  cards.forEach((card) => {
    if (isTouch) {
      /* en touch: girar al tap */
      card.addEventListener('click', () => {
        card.classList.toggle('is-flipped');
      });
    } else {
      /* en desktop: CSS :hover maneja el giro; agregar is-flipped solo por teclado */
      card.addEventListener('mouseenter', () => card.classList.add('is-flipped'));
      card.addEventListener('mouseleave', () => card.classList.remove('is-flipped'));
    }

    /* accesibilidad: Enter / Espacio */
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.classList.toggle('is-flipped');
      }
    });
  });
})();


/* ── Año dinámico en el footer ──────────────────────────────── */
(function () {
  const el = document.getElementById('anioActual');
  if (el) el.textContent = new Date().getFullYear();
})();


/* ── Validación + envío AJAX del formulario ─────────────────── */
(function () {
  const form      = document.getElementById('consultaForm');
  const btnEnviar = document.getElementById('btnEnviar');
  const msgBox    = document.getElementById('formMensaje');

  if (!form) return;

  function showMsg(html, tipo) {
    msgBox.innerHTML = html;
    msgBox.className = tipo === 'ok' ? 'alert-success-form mb-4' : 'alert-error-form mb-4';
    msgBox.style.display = 'block';
    msgBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hideMsg() {
    msgBox.style.display = 'none';
    msgBox.innerHTML = '';
  }

  function validateEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
  }

  function validatePhone(v) {
    return /^[\d\s\+\-\(\)]{7,20}$/.test(v.trim());
  }

  function validateFile(input) {
    if (!input.files || !input.files.length) return true;
    const file = input.files[0];
    const allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    const maxMB = 10 * 1024 * 1024;
    return allowed.includes(file.type) && file.size <= maxMB;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    hideMsg();

    /* --- Validación client-side --- */
    let valid = true;

    const fields = [
      { id: 'nombre',      check: (v) => v.trim().length >= 2 },
      { id: 'telefono',    check: validatePhone },
      { id: 'correo',      check: validateEmail },
      { id: 'comuna',      check: (v) => v.trim().length >= 2 },
      { id: 'tipoCaso',    check: (v) => v !== '' },
      { id: 'etapa',       check: (v) => v !== '' },
    ];

    fields.forEach(({ id, check }) => {
      const el = document.getElementById(id);
      if (!el) return;
      if (!check(el.value)) {
        el.classList.add('is-invalid');
        valid = false;
      } else {
        el.classList.remove('is-invalid');
      }
    });

    /* consentimiento */
    const consent = document.getElementById('consentimiento');
    if (!consent.checked) {
      consent.classList.add('is-invalid');
      valid = false;
    } else {
      consent.classList.remove('is-invalid');
    }

    /* adjunto */
    const adjunto = document.getElementById('adjunto');
    if (!validateFile(adjunto)) {
      adjunto.classList.add('is-invalid');
      valid = false;
    } else {
      adjunto.classList.remove('is-invalid');
    }

    if (!valid) {
      showMsg('Revisa los campos marcados en rojo e inténtalo nuevamente.', 'error');
      return;
    }

    /* --- Envío --- */
    btnEnviar.disabled = true;
    btnEnviar.textContent = 'Enviando…';

    const data = new FormData(form);

    try {
      const res  = await fetch('php/enviar.php', { method: 'POST', body: data });
      const json = await res.json();

      if (json.ok) {
        showMsg(
          '✓ Recibimos tu consulta. Te contactaremos el mismo día.',
          'ok'
        );
        form.reset();
        /* limpiar estados inválidos */
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        window.location.assign('gracias.html');
      } else {
        throw new Error(json.error || 'Error desconocido');
      }
    } catch (err) {
      showMsg(
        'No pudimos enviar tu consulta. Escríbenos por ' +
        '<a href="https://wa.me/56988250630?text=Hola%2C%20quiero%20una%20asesor%C3%ADa%20penal%20confidencial." ' +
        'target="_blank" rel="noopener" style="color:var(--cobre-juridico);font-weight:600;">WhatsApp</a>.',
        'error'
      );
    } finally {
      btnEnviar.disabled = false;
      btnEnviar.textContent = 'Enviar consulta confidencial';
    }
  });

  /* limpiar is-invalid al corregir campo */
  form.querySelectorAll('input, select, textarea').forEach((el) => {
    el.addEventListener('input', () => el.classList.remove('is-invalid'));
    el.addEventListener('change', () => el.classList.remove('is-invalid'));
  });
})();
