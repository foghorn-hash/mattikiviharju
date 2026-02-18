(function ($) {
  var frame;

  function openPreview(src, alt) {
    var modal = $('#cv-project-preview-modal');
    if (!modal.length) {
      return;
    }

    modal.find('.cv-project-preview-image').attr('src', src || '').attr('alt', alt || '');
    modal.addClass('is-visible').attr('aria-hidden', 'false');
  }

  function closePreview() {
    var modal = $('#cv-project-preview-modal');
    if (!modal.length) {
      return;
    }

    modal.removeClass('is-visible').attr('aria-hidden', 'true');
    modal.find('.cv-project-preview-image').attr('src', '').attr('alt', '');
  }

  function updateInput(ids) {
    $('#cv_project_screenshots').val(ids.join(','));
  }

  function collectIds() {
    var ids = [];
    $('#cv-project-screenshots-list .cv-project-screenshot').each(function () {
      var id = parseInt($(this).data('id'), 10);
      if (!isNaN(id)) {
        ids.push(id);
      }
    });
    return ids;
  }

  function renderItem(id, url, alt) {
    var item = $('<div class="cv-project-screenshot" />').attr('data-id', id);
    var img = $('<img />').attr('src', url).attr('data-full', url).attr('alt', alt || '');
    var altInput = $('<input type="text" class="widefat cv-project-screenshot-alt" placeholder="Image alt text" />')
      .attr('name', 'cv_project_screenshot_alts[' + id + ']')
      .val(alt || '');
    var remove = $('<button type="button" class="button-link cv-project-screenshot-remove">Remove</button>');
    item.append(img).append(altInput).append(remove);
    $('#cv-project-screenshots-list').append(item);
  }

  $(document).on('input', '.cv-project-screenshot-alt', function () {
    var val = $(this).val() || '';
    $(this).closest('.cv-project-screenshot').find('img').attr('alt', val);
  });

  $(document).on('click', '.cv-project-screenshot img', function (e) {
    e.preventDefault();
    var full = $(this).attr('data-full') || $(this).attr('src') || '';
    var alt = $(this).attr('alt') || '';
    openPreview(full, alt);
  });

  $(document).on('click', '.cv-project-preview-close', function (e) {
    e.preventDefault();
    closePreview();
  });

  $(document).on('click', '#cv-project-preview-modal', function (e) {
    if (e.target === this) {
      closePreview();
    }
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $('#cv-project-preview-modal').hasClass('is-visible')) {
      closePreview();
    }
  });

  $(document).on('click', '#cv-project-screenshots-add', function (e) {
    e.preventDefault();

    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      title: 'Select screenshots',
      button: { text: 'Use selected' },
      multiple: true,
      library: { type: 'image' }
    });

    frame.on('select', function () {
      var selection = frame.state().get('selection');
      selection.each(function (attachment) {
        var data = attachment.toJSON();
        if (!data || !data.id) {
          return;
        }
        if ($('#cv-project-screenshots-list .cv-project-screenshot[data-id="' + data.id + '"]').length) {
          return;
        }
        var thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
        renderItem(data.id, thumb, data.alt);
      });

      updateInput(collectIds());
    });

    frame.open();
  });

  $(document).on('click', '.cv-project-screenshot-remove', function (e) {
    e.preventDefault();
    $(this).closest('.cv-project-screenshot').remove();
    updateInput(collectIds());
  });
})(jQuery);
