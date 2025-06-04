// Функция для выдвижного меню
const hamburger = document.getElementById('hamburger-menu');
const navLinks = document.getElementById('nav-links');

hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});

// Добавим стиль для отображения меню
document.styleSheets[0].insertRule(`
    #nav-links.active {
        display: block;
    }
`, 0);
