// Main Interactivity Script

document.addEventListener('DOMContentLoaded', () => {



    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('shadow-sm');
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        } else {
            navbar.classList.remove('shadow-sm');
            navbar.style.background = 'rgba(255, 255, 255, 0.9)';
        }
    });

    // Simple Cart/Reservation Simulation - REMOVED to allow PHP logic to work
    // Buttons now link directly to cart_actions.php


    function showToast(message) {
        // Create toast container if not exists
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'glass-panel p-3 mb-2 text-dark border-start border-5 border-primary fade show';
        toast.style.minWidth = '250px';
        toast.innerHTML = `<i class="fas fa-check-circle text-success me-2"></i> ${message}`;

        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.remove('show');
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 3000);
    }

    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.product-card').forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        el.style.transitionDelay = (index % 3) * 100 + 'ms'; // Stagger effect
        observer.observe(el);
    });
});
