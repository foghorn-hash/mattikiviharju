(function () {
  var galleries = document.querySelectorAll('.js-project-gallery');
  if (!galleries.length) {
    return;
  }

  var lightbox = document.querySelector('.project-lightbox');
  var lightboxImage = lightbox ? lightbox.querySelector('.project-lightbox-image') : null;
  var lightboxClose = lightbox ? lightbox.querySelector('.project-lightbox-close') : null;
  var lightboxPrev = lightbox ? lightbox.querySelector('.project-lightbox-prev') : null;
  var lightboxNext = lightbox ? lightbox.querySelector('.project-lightbox-next') : null;

  if (!lightbox || !lightboxImage) {
    return;
  }

  // Check if navigation buttons are available
  var hasNavigation = lightboxPrev && lightboxNext;

  // Collect all screenshots from all galleries
  var allScreenshots = [];
  var currentIndex = 0;

  galleries.forEach(function (gallery) {
    var shots = gallery.querySelectorAll('.project-shot');
    shots.forEach(function (shot) {
      var full = shot.getAttribute('data-full');
      var img = shot.querySelector('img');
      if (full && img) {
        allScreenshots.push({
          url: full,
          alt: img.alt || 'Project screenshot'
        });
      }
    });
  });

  var showScreenshot = function (index) {
    if (index < 0 || index >= allScreenshots.length) {
      return;
    }
    currentIndex = index;
    var screenshot = allScreenshots[currentIndex];
    lightboxImage.src = screenshot.url;
    lightboxImage.alt = screenshot.alt;
    
    // Update button states if navigation is available
    if (hasNavigation) {
      lightboxPrev.disabled = currentIndex === 0;
      lightboxNext.disabled = currentIndex === allScreenshots.length - 1;
    }
    
    lightbox.classList.add('is-visible');
    lightbox.setAttribute('aria-hidden', 'false');
  };

  var closeLightbox = function () {
    lightbox.classList.remove('is-visible');
    lightbox.setAttribute('aria-hidden', 'true');
    lightboxImage.src = '';
    lightboxImage.alt = '';
  };

  var goToPrev = function () {
    if (currentIndex > 0) {
      showScreenshot(currentIndex - 1);
    }
  };

  var goToNext = function () {
    if (currentIndex < allScreenshots.length - 1) {
      showScreenshot(currentIndex + 1);
    }
  };

  // Handle gallery clicks
  galleries.forEach(function (gallery) {
    gallery.addEventListener('click', function (event) {
      var button = event.target.closest('.project-shot');
      if (!button) {
        return;
      }

      var full = button.getAttribute('data-full');
      if (!full) {
        return;
      }

      // Find the index of this screenshot in allScreenshots
      var index = allScreenshots.findIndex(function (shot) {
        return shot.url === full;
      });
      
      if (index !== -1) {
        showScreenshot(index);
      }
    });
  });

  // Handle navigation buttons
  if (hasNavigation) {
    lightboxPrev.addEventListener('click', goToPrev);
    lightboxNext.addEventListener('click', goToNext);
  }

  // Handle close button
  if (lightboxClose) {
    lightboxClose.addEventListener('click', function (event) {
      event.stopPropagation();
      closeLightbox();
    });
  }

  // Handle lightbox background click
  lightbox.addEventListener('click', function (event) {
    if (event.target === lightbox) {
      closeLightbox();
    }
  });

  // Handle keyboard navigation
  document.addEventListener('keydown', function (event) {
    if (!lightbox.classList.contains('is-visible')) {
      return;
    }
    
    if (event.key === 'Escape') {
      closeLightbox();
    } else if (event.key === 'ArrowLeft') {
      goToPrev();
    } else if (event.key === 'ArrowRight') {
      goToNext();
    }
  });
})();

