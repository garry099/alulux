(function () {
  // Мобильное меню
  function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const body = document.body;

    if (menuToggle && mainNav) {
      menuToggle.addEventListener('click', function () {
        const isMenuOpen = mainNav.classList.contains('is-open');

        this.classList.toggle('active');
        mainNav.classList.toggle('is-open');
        this.setAttribute('aria-expanded', !isMenuOpen);

        if (!isMenuOpen) {
          const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
          body.style.paddingRight = `${scrollbarWidth}px`;
          body.classList.add('mobile-menu-open');
        } else {
          body.style.paddingRight = '';
          body.classList.remove('mobile-menu-open');
        }
      });

      mainNav.addEventListener('click', function (event) {
        if (event.target.tagName === 'A' && event.target.hash) {
          window.location.href = 'index.html' + event.target.hash;
          closeMobileMenu();
          event.preventDefault();
        }
      });

      function closeMobileMenu() {
        menuToggle.classList.remove('active');
        mainNav.classList.remove('is-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        body.classList.remove('mobile-menu-open');
        body.style.paddingRight = '';
      }
    }
  }

  // Обновление года в футере
  function updateFooterYear() {
    const currentYear = new Date().getFullYear();
    const yearElements = document.querySelectorAll('.copyright-year');
    yearElements.forEach(element => {
      element.textContent = currentYear;
    });
  }

  // Подсветка активного раздела
  function initHighlightActiveSection() {
    const sections = document.querySelectorAll('main section[id]');
    const navLinks = document.querySelectorAll('header .main-nav ul li a[href^="#"]');
    const header = document.querySelector('header');
    const headerOffset = 40;

    function changeActiveLink() {
      let currentSectionId = '';

      sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.offsetHeight;

        if (
          window.scrollY >= sectionTop - headerOffset &&
          window.scrollY < sectionTop + sectionHeight - headerOffset
        ) {
          currentSectionId = section.getAttribute('id');
        }
      });

      navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${currentSectionId}`) {
          link.classList.add('active');
        }
      });
    }

    window.addEventListener('scroll', changeActiveLink);
  }

  // Инициализация всего
  function initHeader() {
    initMobileMenu();
    initHighlightActiveSection();
  }

  // Вызываем функцию обновления года при загрузке страницы
  document.addEventListener('DOMContentLoaded', () => {
    updateFooterYear();
  });

  window.initHeader = initHeader;
})();
