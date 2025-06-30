// image-optimization.js

document.addEventListener('DOMContentLoaded', function() {
    // Оптимизация изображений при загрузке страницы
    optimizeImages();
    
    // Обработка изображений при скролле
    window.addEventListener('scroll', optimizeImages);
});

function optimizeImages() {
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        // Проверяем, видно ли изображение в области просмотра
        if (isInViewport(img)) {
            // Если у изображения есть data-src, используем его как источник
            if (img.dataset.src) {
                img.src = img.dataset.src;
                delete img.dataset.src;
            }
            
            // Оптимизация размера изображения
            optimizeImageSize(img);
            
            // Улучшение качества отображения
            img.style.imageRendering = 'crisp-edges';
            
            // Удаление обработчика после оптимизации
            img.style.opacity = '1';
        }
    });
}

function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

function optimizeImageSize(img) {
    // Максимальный размер изображения в пикселях
    const maxWidth = 1920;
    const maxHeight = 1080;
    
    // Получаем размеры изображения
    const imgWidth = img.naturalWidth || img.width;
    const imgHeight = img.naturalHeight || img.height;
    
    // Если изображение больше максимального размера, масштабируем
    if (imgWidth > maxWidth || imgHeight > maxHeight) {
        const scale = Math.min(maxWidth / imgWidth, maxHeight / imgHeight);
        img.width = imgWidth * scale;
        img.height = imgHeight * scale;
    }
}
