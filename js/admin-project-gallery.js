(function ($) {
  var frame;

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
    var img = $('<img />').attr('src', url).attr('alt', alt || '');
    var remove = $('<button type="button" class="button-link cv-project-screenshot-remove">Remove</button>');
    item.append(img).append(remove);
    $('#cv-project-screenshots-list').append(item);
  }

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
