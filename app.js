/* ══════════════════════════════════════════
   COOPERATIVA FLB — Main JS
   Cross-browser: Chrome, Edge, Firefox, Safari
   Mobile-safe, no ES6+ features inseguras
   ══════════════════════════════════════════ */
(function () {
  'use strict';

  /* ── Navbar scroll ── */
  var navbar = document.getElementById('navbar');
  if (navbar) {
    function onScroll() {
      if (window.pageYOffset > 30) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ── Hamburger menu ── */
  var hamburger  = document.getElementById('hamburger');
  var mobileMenu = document.getElementById('mobileMenu');
  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function () {
      mobileMenu.classList.toggle('open');
    });
    // Cerrar al hacer click fuera
    document.addEventListener('click', function (e) {
      if (mobileMenu.classList.contains('open') &&
          !hamburger.contains(e.target) &&
          !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('open');
      }
    });
  }

  /* ── Admin sidebar toggle (mobile) ── */
  var sidebarToggle = document.getElementById('sidebarToggle');
  var adminSidebar  = document.getElementById('adminSidebar');
  if (sidebarToggle && adminSidebar) {
    sidebarToggle.addEventListener('click', function () {
      adminSidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (adminSidebar.classList.contains('open') &&
          !adminSidebar.contains(e.target) &&
          !sidebarToggle.contains(e.target)) {
        adminSidebar.classList.remove('open');
      }
    });
  }

  /* ── Contador animado ── */
  function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

  function formatMoney(val) {
    return '$' + Math.round(val).toLocaleString('es-AR');
  }

  function animateCounter(el) {
    var target   = parseFloat(el.getAttribute('data-target')) || 0;
    var prefix   = el.getAttribute('data-prefix') || '';
    var fmt      = el.getAttribute('data-format') || '';
    var duration = 1800;
    var start    = null;

    function tick(now) {
      if (!start) start = now;
      var elapsed  = now - start;
      var progress = Math.min(elapsed / duration, 1);
      var value    = target * easeOutCubic(progress);
      if (fmt === 'money') {
        el.textContent = formatMoney(value);
      } else {
        el.textContent = prefix + Math.round(value).toLocaleString('es-AR');
      }
      if (progress < 1) {
        requestAnimationFrame(tick);
      } else {
        if (fmt === 'money') el.textContent = formatMoney(target);
        else el.textContent = prefix + Math.round(target).toLocaleString('es-AR');
      }
    }
    requestAnimationFrame(tick);
  }

  /* ── IntersectionObserver seguro ── */
  function safeObserve(elements, callback, threshold) {
    if (!elements || elements.length === 0) return;
    if (!('IntersectionObserver' in window)) {
      // Fallback: ejecutar todo inmediatamente
      for (var i = 0; i < elements.length; i++) {
        callback(elements[i]);
      }
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          callback(entry.target);
          io.unobserve(entry.target);
        }
      });
    }, { threshold: threshold || 0.2 });
    for (var j = 0; j < elements.length; j++) {
      io.observe(elements[j]);
    }
  }

  /* ── Contadores on-scroll ── */
  var counters = document.querySelectorAll('.counter');
  safeObserve(counters, animateCounter, 0.3);

  /* ── Scroll reveal ── */
  var animEls = document.querySelectorAll('.animate-up, .animate-in');
  if ('IntersectionObserver' in window) {
    animEls.forEach(function (el) {
      el.style.animationPlayState = 'paused';
    });
    safeObserve(animEls, function (el) {
      el.style.animationPlayState = 'running';
    }, 0.05);
  }
  // Si no hay IO, las animaciones corren normalmente (sin pausa)

  /* ── Progress bars animadas ── */
  var fills = document.querySelectorAll('.progress-bar-fill');
  fills.forEach(function (bar) {
    var target = bar.style.getPropertyValue('--pct') || bar.getAttribute('data-pct') || '0%';
    bar.style.width = '0%';
    safeObserve([bar], function () {
      setTimeout(function () { bar.style.width = target; }, 150);
    }, 0.3);
  });

  /* ── Campaign progress fill ── */
  var cpFills = document.querySelectorAll('.cp-fill');
  cpFills.forEach(function (fill) {
    var tw = fill.getAttribute('data-width') || fill.style.width || '0%';
    if (!fill.getAttribute('data-width')) fill.setAttribute('data-width', tw);
    fill.style.width = '0%';
    safeObserve([fill], function () {
      setTimeout(function () { fill.style.width = tw; }, 150);
    }, 0.3);
  });

  /* ── Particle canvas (solo desktop) ── */
  var canvas = document.getElementById('particleCanvas');
  if (canvas) {
    // En móvil o pantallas chicas, no renderizar partículas
    var isMobile = window.innerWidth < 768 ||
      /Mobi|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

    if (!isMobile && canvas.getContext) {
      var ctx = canvas.getContext('2d');
      var particles = [];
      var W, H;

      function resize() {
        W = canvas.width  = canvas.offsetWidth  || canvas.parentElement.offsetWidth  || 800;
        H = canvas.height = canvas.offsetHeight || canvas.parentElement.offsetHeight || 600;
      }

      function Particle() {
        this.reset = function () {
          this.x  = Math.random() * W;
          this.y  = Math.random() * H;
          this.vx = (Math.random() - 0.5) * 0.4;
          this.vy = (Math.random() - 0.5) * 0.4 - 0.15;
          this.r  = Math.random() * 1.5 + 0.4;
          this.a  = Math.random() * 0.5 + 0.1;
          this.da = (Math.random() - 0.5) * 0.005;
        };
        this.reset();
        this.update = function () {
          this.x += this.vx;
          this.y += this.vy;
          this.a += this.da;
          if (this.a < 0 || this.a > 0.65) this.da *= -1;
          if (this.y < -10 || this.x < -10 || this.x > W + 10) this.reset();
        };
        this.draw = function () {
          ctx.beginPath();
          ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
          ctx.fillStyle = 'rgba(167,139,250,' + this.a + ')';
          ctx.fill();
        };
      }

      function initParticles() {
        resize();
        particles = [];
        var count = Math.min(60, Math.floor((W * H) / 15000));
        for (var i = 0; i < count; i++) {
          particles.push(new Particle());
        }
      }

      var raf;
      function loop() {
        ctx.clearRect(0, 0, W, H);
        for (var i = 0; i < particles.length; i++) {
          for (var j = i + 1; j < particles.length; j++) {
            var dx = particles[i].x - particles[j].x;
            var dy = particles[i].y - particles[j].y;
            var d  = Math.sqrt(dx * dx + dy * dy);
            if (d < 100) {
              ctx.beginPath();
              ctx.strokeStyle = 'rgba(124,58,237,' + (1 - d / 100) * 0.12 + ')';
              ctx.lineWidth = 0.5;
              ctx.moveTo(particles[i].x, particles[i].y);
              ctx.lineTo(particles[j].x, particles[j].y);
              ctx.stroke();
            }
          }
          particles[i].update();
          particles[i].draw();
        }
        raf = requestAnimationFrame(loop);
      }

      var resizeTimer;
      window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
          cancelAnimationFrame(raf);
          initParticles();
          loop();
        }, 300);
      });

      initParticles();
      loop();
    } else if (canvas) {
      // Ocultar canvas en móvil para no ocupar espacio
      canvas.style.display = 'none';
    }
  }

  /* ── Toggle contraseña ── */
  window.togglePass = function (inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.textContent = show ? '\uD83D\uDE48' : '\uD83D\uDC41'; // 🙈 / 👁
  };

  /* ── Auto-dismiss alertas ── */
  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity .4s ease, transform .4s ease';
      alert.style.opacity    = '0';
      alert.style.transform  = 'translateY(-6px)';
      setTimeout(function () {
        if (alert.parentNode) alert.parentNode.removeChild(alert);
      }, 400);
    }, 5000);
  });

  /* ── Fix iOS min-height 100vh ── */
  function setVh() {
    var vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', vh + 'px');
  }
  window.addEventListener('resize', setVh);
  setVh();

  /* ── Fix select en iOS ── */
  var selects = document.querySelectorAll('select');
  selects.forEach(function (sel) {
    sel.addEventListener('touchend', function (e) {
      e.stopPropagation();
    });
  });

  /* ══════════════════════════════════════════
     🥚 EASTER EGG
     Trigger 1: Konami Code → ↑↑↓↓←→←→
     Trigger 2: Tap 7 veces rápido en el logo (mobile)
     ══════════════════════════════════════════ */
  (function () {
    var IMG_SRC = 'assets/easter.png';

    var mensajes = [
      '¡El elegido ha llegado! 🙌',
      '¡Bendecidos sean los donantes! ✨',
      '¡El patrono de la cooperativa! 😇',
      'Konami Code desbloqueado 🎮',
      '¡Gracias por encontrarme! 🙏',
    ];

    var mostrado = false;

    function lanzarEgg() {
      if (mostrado) return;
      mostrado = true;

      var wrap = document.createElement('div');
      wrap.id = 'easter-egg-wrap';
      wrap.style.cssText = [
        'position:fixed',
        'bottom:16px',
        'right:16px',
        'z-index:99999',
        'display:flex',
        'flex-direction:column',
        'align-items:center',
        'pointer-events:none',
        'opacity:0',
        'transform:translateY(20px) scale(0.6)',
        'transition:opacity 0.35s cubic-bezier(.17,.67,.31,1.3), transform 0.35s cubic-bezier(.17,.67,.31,1.3)',
      ].join(';');

      var img = document.createElement('img');
      img.src = IMG_SRC;
      img.alt = '';
      img.style.cssText = [
        'width:75px',
        'height:auto',
        'display:block',
        'filter:drop-shadow(0 3px 10px rgba(124,58,237,0.6))',
      ].join(';');

      var msg = document.createElement('div');
      msg.textContent = mensajes[Math.floor(Math.random() * mensajes.length)];
      msg.style.cssText = [
        'background:linear-gradient(135deg,#2D1B69,#7C3AED)',
        'color:#EDE9F6',
        'font-family:Raleway,Arial,sans-serif',
        'font-size:10px',
        'font-weight:700',
        'padding:4px 8px',
        'border-radius:20px',
        'margin-top:5px',
        'text-align:center',
        'white-space:nowrap',
        'box-shadow:0 2px 10px rgba(124,58,237,0.5)',
      ].join(';');

      wrap.appendChild(img);
      wrap.appendChild(msg);
      document.body.appendChild(wrap);

      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          wrap.style.opacity = '1';
          wrap.style.transform = 'translateY(0) scale(1)';

          var frame = 0;
          var wiggle = setInterval(function () {
            frame++;
            img.style.transform = 'rotate(' + (Math.sin(frame * 0.8) * 7) + 'deg)';
            if (frame > 20) { clearInterval(wiggle); img.style.transform = 'rotate(0deg)'; }
          }, 50);
        });
      });

      setTimeout(function () {
        wrap.style.opacity = '0';
        wrap.style.transform = 'translateY(20px) scale(0.6)';
        setTimeout(function () {
          if (wrap.parentNode) wrap.parentNode.removeChild(wrap);
          mostrado = false;
        }, 400);
      }, 3200);
    }

    /* ─── Konami Code ↑↑↓↓←→←→ ─── */
    var konamiOk = ['ArrowUp','ArrowUp','ArrowDown','ArrowDown','ArrowLeft','ArrowRight','ArrowLeft','ArrowRight'];
    var konami   = [38,38,40,40,37,39,37,39];
    var kIdx = 0;
    document.addEventListener('keydown', function (e) {
      if ((e.key || '') === konamiOk[kIdx] || e.keyCode === konami[kIdx]) {
        kIdx++;
        if (kIdx === konami.length) { kIdx = 0; lanzarEgg(); }
      } else {
        kIdx = 0;
        if ((e.key || '') === konamiOk[0] || e.keyCode === konami[0]) kIdx = 1;
      }
    });

    /* ─── 7 taps en el logo (mobile) ─── */
    var tapCount = 0, tapTimer = null;
    function setupLogoTap() {
      document.querySelectorAll('.nav-logo, .auth-logo, .sidebar-logo').forEach(function (logo) {
        logo.addEventListener('click', function () {
          tapCount++;
          clearTimeout(tapTimer);
          tapTimer = setTimeout(function () { tapCount = 0; }, 1500);
          if (tapCount >= 7) { tapCount = 0; lanzarEgg(); }
        });
      });
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', setupLogoTap);
    } else {
      setupLogoTap();
    }

  })();

})();
