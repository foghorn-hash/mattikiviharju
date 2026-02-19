(function () {
  var galleries = document.querySelectorAll('.js-project-gallery');
  if (!galleries.length) {
    return;
  }

  var forEachNode = function (nodes, callback) {
    Array.prototype.forEach.call(nodes, callback);
  };

  var findClosestProjectShot = function (target, container) {
    var element = target;

    while (element && element !== container) {
      if (element.nodeType === 1 && element.classList && element.classList.contains('project-shot')) {
        return element;
      }
      element = element.parentNode;
    }

    return null;
  };

  var lightbox = document.querySelector('.project-lightbox');
  var lightboxImage = lightbox ? lightbox.querySelector('.project-lightbox-image') : null;
  var lightboxVideo = lightbox ? lightbox.querySelector('.project-lightbox-video') : null;
  var lightboxCaption = lightbox ? lightbox.querySelector('.project-lightbox-caption strong') : null;
  var lightboxClose = lightbox ? lightbox.querySelector('.project-lightbox-close') : null;
  var lightboxPrev = lightbox ? lightbox.querySelector('.project-lightbox-prev') : null;
  var lightboxNext = lightbox ? lightbox.querySelector('.project-lightbox-next') : null;

  if (!lightbox || !lightboxImage || !lightboxVideo) {
    return;
  }

  // Check if navigation buttons are available
  var hasNavigation = lightboxPrev && lightboxNext;

  var activeItems = [];
  var currentIndex = 0;

  var collectGalleryItems = function (gallery) {
    var items = [];
    var shots = gallery.querySelectorAll('.project-shot');
    forEachNode(shots, function (shot) {
      var full = shot.getAttribute('data-full');
      var youtube = shot.getAttribute('data-youtube');
      var img = shot.querySelector('img');
      
      if (youtube) {
        items.push({
          type: 'video',
          youtubeId: youtube,
          alt: img ? (img.alt || 'Project video') : 'Project video',
          source: shot
        });
      } else if (full && img) {
        items.push({
          type: 'image',
          url: full,
          alt: img.alt || 'Project screenshot',
          source: shot
        });
      }
    });

    return items;
  };

  var showItem = function (index) {
    if (index < 0 || index >= activeItems.length) {
      return;
    }
    currentIndex = index;
    var item = activeItems[currentIndex];
    if (!item) {
      return;
    }
    
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

    if (lightboxCaption) {
      lightboxCaption.textContent = item.alt || String(currentIndex + 1);
    }
    
    // Update button states if navigation is available
    if (hasNavigation) {
      var hasMultipleItems = activeItems.length > 1;
      lightboxPrev.disabled = !hasMultipleItems || currentIndex === 0;
      lightboxNext.disabled = !hasMultipleItems || currentIndex === activeItems.length - 1;
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
    if (lightboxCaption) {
      lightboxCaption.textContent = '';
    }
  };

  var goToPrev = function () {
    if (currentIndex > 0) {
      showItem(currentIndex - 1);
    }
  };

  var goToNext = function () {
    if (currentIndex < activeItems.length - 1) {
      showItem(currentIndex + 1);
    }
  };

  // Handle gallery clicks
  forEachNode(galleries, function (gallery) {
    gallery.addEventListener('click', function (event) {
      var button = findClosestProjectShot(event.target, gallery);
      if (!button) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      var full = button.getAttribute('data-full');
      var youtube = button.getAttribute('data-youtube');
      
      if (!full && !youtube) {
        return;
      }

      activeItems = collectGalleryItems(gallery);
      if (!activeItems.length) {
        return;
      }

      var index = -1;
      var i;
      for (i = 0; i < activeItems.length; i += 1) {
        if (activeItems[i].source === button) {
          index = i;
          break;
        }
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

