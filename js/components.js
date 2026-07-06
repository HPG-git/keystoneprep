/* ─────────────────────────────────────────────
   Keystone Prep — Shared Components
   Usage in HTML:
     <kp-nav></kp-nav>
     <kp-footer tagline="Your tagline here."></kp-footer>

   Active nav link is set automatically based on
   the current page filename.
───────────────────────────────────────────── */

const NAV_LINKS = [
  {
    label: 'About',
    href: '/about-us.html',
    children: [
      { label: 'Staff',             href: '/faculty-staff.html'    },
      { label: 'Board of Trustees', href: '/board-of-trustees.html' },
      { label: 'Contact Us',        href: '/contact.html'          },
      { label: 'Report Misconduct', href: '/report-misconduct.html' },
      { label: 'Our Newsletter',    href: 'https://word.keystoneprep.org/newsletter-2/', newTab: true },
    ],
  },
  {
    label: 'Admissions',
    href: '/admissions.html',
    children: [
      { label: 'Apply Now',     href: '/apply.html'   },
      { label: 'Tuition & Aid', href: '/tuition.html' },
      { label: 'Payment',       href: 'https://word.keystoneprep.org/payment/' },
    ],
  },
  {
    label: 'Academics',
    href: '/academics.html',
    children: [
      { label: 'Our Magnet Programs', href: '/programs.html' },
    ],
  },
  { label: 'Giving',          href: '/giving.html'       },
  { label: 'Alumni',          href: '/alumni.html'       },
  { label: 'Summer Programs', href: '/summer-camps.html' },
];

const ICON_CHEVRON = `<svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor"
  stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
  <path d="M6 9l6 6 6-6"/>
</svg>`;

const LOGO_SVG = `<svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"
  stroke-linecap="round" stroke-linejoin="round">
  <path d="M12 2L2 7l10 5 10-5-10-5z"/>
  <path d="M2 17l10 5 10-5"/>
  <path d="M2 12l10 5 10-5"/>
</svg>`;

const ICON_SEARCH = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
  stroke-linecap="round" stroke-linejoin="round">
  <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
</svg>`;

const ICON_MENU = `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
  stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
  <line x1="3" y1="6" x2="21" y2="6"/>
  <line x1="3" y1="12" x2="21" y2="12"/>
  <line x1="3" y1="18" x2="21" y2="18"/>
</svg>`;

/* Detect which page is active from the URL */
function getActivePage() {
  const path = window.location.pathname;
  const filename = path.split('/').pop() || 'index.html';
  return filename;
}

/* Build nav link HTML, marking the active page */
function buildNavLinks(activePage) {
  return NAV_LINKS.map(({ label, href, children }) => {
    if (children && children.length) {
      const isActive = activePage === href || children.some(c => c.href === activePage);
      const selfLink = `<a href="${href}" class="nav-dropdown-link nav-dropdown-self${activePage === href ? ' active' : ''}">${label} Overview</a>`;
      const childLinks = children.map(c =>
        `<a href="${c.href}" class="nav-dropdown-link${activePage === c.href ? ' active' : ''}"${c.newTab ? ' target="_blank" rel="noopener noreferrer"' : ''}>${c.label}</a>`
      ).join('');
      return `<div class="nav-item${isActive ? ' active' : ''}">
        <a href="${href}" class="nav-link${isActive ? ' active' : ''}">${label}${ICON_CHEVRON}</a>
        <div class="nav-dropdown">${selfLink}${childLinks}</div>
      </div>`;
    }
    const isActive = activePage === href;
    return `<a href="${href}" class="nav-link${isActive ? ' active' : ''}">${label}</a>`;
  }).join('\n        ');
}

/* ── KP-NAV ─────────────────────────────────── */
class KpNav extends HTMLElement {
  connectedCallback() {
    const activePage = getActivePage();
    this.outerHTML = `
<header class="nav" id="kp-nav">
  <div class="nav-inner">
    <a href="/index.html" class="nav-logo">
      <div class="nav-logo-mark"><img src="/img/logo-keystone-2.png" alt="Keystone Prep logo" /></div>
      <div class="nav-logo-text">
        <strong>Keystone Prep</strong>
        <span>High School</span>
      </div>
    </a>
    <nav class="nav-links" id="kp-nav-links">
      ${buildNavLinks(activePage)}
    </nav>
    <div class="nav-actions">
      <a href="tel:8132644500" class="nav-phone" aria-label="Call Keystone Prep">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.67 9.5a19.79 19.79 0 0 1-3.07-8.57A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        <span>(813) 264-4500</span>
      </a>
      <a href="/schedule-tour.html" class="btn-apply-now">Schedule a Tour</a>
    </div>
    <button class="nav-hamburger" id="kp-hamburger" aria-label="Open menu" aria-expanded="false">${ICON_MENU}</button>
  </div>
</header>`;

    /* Hamburger toggle — runs after outerHTML replaces the element */
    requestAnimationFrame(() => {
      const hamburger = document.getElementById('kp-hamburger');
      const navLinks  = document.getElementById('kp-nav-links');
      if (!hamburger || !navLinks) return;

      hamburger.addEventListener('click', () => {
        const open = navLinks.classList.toggle('nav-links-open');
        hamburger.setAttribute('aria-expanded', open);
        hamburger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        hamburger.innerHTML = open ? `<svg width="22" height="22" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>` : ICON_MENU;
      });

      /* Mobile: toggle dropdown sub-items when parent link is tapped */
      navLinks.querySelectorAll('.nav-item .nav-link').forEach(parentLink => {
        parentLink.addEventListener('click', e => {
          if (window.innerWidth <= 900) {
            e.preventDefault();
            const item = parentLink.closest('.nav-item');
            item.classList.toggle('dropdown-open');
          }
        });
      });

      /* Close menu when a leaf nav link or dropdown link is tapped */
      navLinks.querySelectorAll('a:not(.nav-item .nav-link), .nav-dropdown-link').forEach(a => {
        a.addEventListener('click', () => {
          navLinks.classList.remove('nav-links-open');
          hamburger.setAttribute('aria-expanded', 'false');
          hamburger.setAttribute('aria-label', 'Open menu');
          hamburger.innerHTML = ICON_MENU;
        });
      });
    });
  }
}

/* ── KP-FOOTER ──────────────────────────────── */
class KpFooter extends HTMLElement {
  connectedCallback() {
    const tagline = this.getAttribute('tagline')
      || 'Personalized support. Meaningful learning. Real-world preparation.';

    this.outerHTML = `
<footer>
  <div class="footer-top-bar"></div>
  <div class="footer-main">
    <div class="footer-brand-col">
      <img src="/img/logo-keystone-1.png" alt="Keystone Prep" class="footer-brand-logo" />
      <p class="footer-tagline">${tagline}</p>
      <div class="footer-socials">
        <a href="https://keystoneprep.org/#keystoneprep" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Facebook">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
          </svg>
        </a>
        <a href="https://www.instagram.com/keystoneprephs/" target="_blank" rel="noopener noreferrer" class="social-btn" aria-label="Instagram">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
          </svg>
        </a>
      </div>
    </div>

    <div class="footer-links-group">
      <div class="footer-col">
        <h4>Explore</h4>
        <div class="footer-links">
          <a href="about-us.html"          class="footer-link">About Us</a>
          <a href="faculty-staff.html"     class="footer-link footer-link-sub">Staff</a>
          <a href="board-of-trustees.html" class="footer-link footer-link-sub">Board of Trustees</a>
          <a href="contact.html"           class="footer-link footer-link-sub">Contact Us</a>
          <a href="report-misconduct.html" class="footer-link footer-link-sub">Report Misconduct</a>
          <a href="academics.html"         class="footer-link">Academics</a>
          <a href="programs.html"          class="footer-link footer-link-sub">Our Magnet Programs</a>
          <a href="admissions.html"        class="footer-link">Admissions</a>
          <a href="tuition.html"           class="footer-link footer-link-sub">Tuition &amp; Aid</a>
          <a href="apply.html"             class="footer-link footer-link-sub">Apply Now</a>
          <a href="summer-camps.html"      class="footer-link">Summer Programs</a>
          <a href="giving.html"            class="footer-link">Giving</a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Resources</h4>
        <div class="footer-links">
          <a href="/schedule-tour.html" class="footer-link">Schedule a Tour</a>
          <a href="tuition.html"       class="footer-link">Tuition &amp; Aid</a>
          <a href="contact.html"       class="footer-link">Contact Us</a>
          <a href="/careers.html"       class="footer-link">Careers</a>
          <a href="/privacy.html"       class="footer-link">Privacy Policy</a>
          <a href="/terms.html"         class="footer-link">Terms of Service</a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Contact</h4>
        <div class="footer-contact-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          <span>18105 Gunn Hwy<br>Odessa, Florida 33556</span>
        </div>
        <div class="footer-contact-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.67 9.5a19.79 19.79 0 0 1-3.07-8.57A2 2 0 0 1 3.77 1h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
          <a href="tel:8132644500">(813) 264-4500</a>
        </div>
        <div class="footer-contact-item">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
          </svg>
          <a href="mailto:info@keystoneprep.org">info@keystoneprep.org</a>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; ${new Date().getFullYear()} Keystone Prep High School. All rights reserved.</p>
    <div class="footer-bottom-links">
      <a href="/privacy.html">Privacy Policy</a>
      <a href="/terms.html">Terms of Use</a>
    </div>
  </div>
</footer>`;
  }
}

customElements.define('kp-nav',    KpNav);
customElements.define('kp-footer', KpFooter);
