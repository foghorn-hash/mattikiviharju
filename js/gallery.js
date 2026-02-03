(function () {
  var galleries = document.querySelectorAll('.js-project-gallery');
  if (!galleries.length) {
    return;
  }

  var lightbox = document.querySelector('.project-lightbox');
  var lightboxImage = lightbox ? lightbox.querySelector('.project-lightbox-image') : null;
  var lightboxClose = lightbox ? lightbox.querySelector('.project-lightbox-close') : null;

  if (!lightbox || !lightboxImage) {
    return;
  }

  var closeLightbox = function () {
    lightbox.classList.remove('is-visible');
    lightbox.setAttribute('aria-hidden', 'true');
    lightboxImage.src = '';
    lightboxImage.alt = '';
  };

  galleries.forEach(function (gallery) {
    gallery.addEventListener('click', function (event) {
      var button = event.target.closest('.project-shot');
      if (!button) {
        return;
      }

      var full = button.getAttribute('data-full');
      var img = button.querySelector('img');
      if (!full || !img) {
        return;
      }

      lightboxImage.src = full;
      lightboxImage.alt = img.alt || 'Project screenshot';
      lightbox.classList.add('is-visible');
      lightbox.setAttribute('aria-hidden', 'false');
    });
  });

  lightbox.addEventListener('click', function (event) {
    if (event.target === lightbox || event.target === lightboxClose) {
      closeLightbox();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && lightbox.classList.contains('is-visible')) {
      closeLightbox();
    }
  });
})();
