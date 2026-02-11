// Sticky Navigation
window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav');
    if (window.scrollY > 50) {
        nav.classList.add('sticky');
    } else {
        nav.classList.remove('sticky');
    }
});

// Intersection Observer for Animations
const observerOptions = {
    threshold: 0.1
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('aos-animate');
        }
    });
}, observerOptions);

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-aos]').forEach(el => {
        observer.observe(el);
    });
});

// Smooth Scroll for local links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });

            // Close mobile menu if open
            const toggle = document.getElementById('menu-toggle');
            if (toggle) toggle.checked = false;
        }
    });
});

// Lang selector toggle on mobile
const langBtn = document.querySelector('.lang-btn');
const langSelector = document.querySelector('.lang-selector');
if (langBtn && langSelector) {
    langBtn.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            e.preventDefault();
            e.stopPropagation();
            langSelector.classList.toggle('active');
        }
    });

    document.addEventListener('click', () => {
        langSelector.classList.remove('active');
    });
}
