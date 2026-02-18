(function () {
  var galleries = document.querySelectorAll('.js-project-gallery');
  if (!galleries.length) {
    return;
  }

  var lightbox = document.querySelector('.project-lightbox');
  var lightboxImage = lightbox ? lightbox.querySelector('.project-lightbox-image') : null;
  var lightboxVideo = lightbox ? lightbox.querySelector('.project-lightbox-video') : null;
  var lightboxClose = lightbox ? lightbox.querySelector('.project-lightbox-close') : null;
  var lightboxPrev = lightbox ? lightbox.querySelector('.project-lightbox-prev') : null;
  var lightboxNext = lightbox ? lightbox.querySelector('.project-lightbox-next') : null;

  if (!lightbox || !lightboxImage || !lightboxVideo) {
    return;
  }

  // Check if navigation buttons are available
  var hasNavigation = lightboxPrev && lightboxNext;

  // Collect all screenshots and videos from all galleries
  var allItems = [];
  var currentIndex = 0;

  galleries.forEach(function (gallery) {
    var shots = gallery.querySelectorAll('.project-shot');
    shots.forEach(function (shot) {
      var full = shot.getAttribute('data-full');
      var youtube = shot.getAttribute('data-youtube');
      var img = shot.querySelector('img');
      
      if (youtube) {
        // YouTube video
        allItems.push({
          type: 'video',
          youtubeId: youtube,
          alt: img ? (img.alt || 'Project video') : 'Project video'
        });
      } else if (full && img) {
        // Image screenshot
        allItems.push({
          type: 'image',
          url: full,
          alt: img.alt || 'Project screenshot'
        });
      }
    });
  });

  var showItem = function (index) {
    if (index < 0 || index >= allItems.length) {
      return;
    }
    currentIndex = index;
    var item = allItems[currentIndex];
    
    // Hide both image and video first
    lightboxImage.style.display = 'none';
    lightboxImage.src = '';
    lightboxImage.alt = '';
    lightboxVideo.style.display = 'none';
    lightboxVideo.innerHTML = '';
    
    if (item.type === 'video') {
      // Show YouTube video
      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube.com/embed/' + item.youtubeId + '?autoplay=1';
      iframe.setAttribute('frameborder', '0');
      iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
      iframe.setAttribute('allowfullscreen', '');
      iframe.setAttribute('title', item.alt);
      lightboxVideo.appendChild(iframe);
      lightboxVideo.style.display = 'block';
    } else {
      // Show image
      lightboxImage.src = item.url;
      lightboxImage.alt = item.alt;
      lightboxImage.style.display = 'block';
    }
    
    // Update button states if navigation is available
    if (hasNavigation) {
      lightboxPrev.disabled = currentIndex === 0;
      lightboxNext.disabled = currentIndex === allItems.length - 1;
    }
    
    lightbox.classList.add('is-visible');
    lightbox.setAttribute('aria-hidden', 'false');
  };

  var closeLightbox = function () {
    lightbox.classList.remove('is-visible');
    lightbox.setAttribute('aria-hidden', 'true');
    lightboxImage.src = '';
    lightboxImage.alt = '';
    lightboxImage.style.display = 'none';
    lightboxVideo.innerHTML = '';
    lightboxVideo.style.display = 'none';
  };

  var goToPrev = function () {
    if (currentIndex > 0) {
      showItem(currentIndex - 1);
    }
  };

  var goToNext = function () {
    if (currentIndex < allItems.length - 1) {
      showItem(currentIndex + 1);
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
      var youtube = button.getAttribute('data-youtube');
      
      if (!full && !youtube) {
        return;
      }

      // Find the index of this item in allItems
      var index = -1;
      if (youtube) {
        index = allItems.findIndex(function (item) {
          return item.type === 'video' && item.youtubeId === youtube;
        });
      } else if (full) {
        index = allItems.findIndex(function (item) {
          return item.type === 'image' && item.url === full;
        });
      }
      
      if (index !== -1) {
        showItem(index);
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

