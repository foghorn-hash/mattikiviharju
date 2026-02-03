(function ($) {
  "use strict";

  var $list = $(".js-post-list");
  var $button = $(".js-load-more");
  var $spinner = $(".js-load-spinner");

  if (!$list.length || !$button.length) {
    return;
  }

  var offset = parseInt($list.data("offset"), 10) || 0;
  var perPage = parseInt(cvBlog.perPage, 10) || 6;

  function setLoading(isLoading) {
    $button.prop("disabled", isLoading);
    $spinner.toggleClass("is-visible", isLoading);
  }

  function loadPosts() {
    setLoading(true);

    $.ajax({
      url: cvBlog.ajaxUrl,
      method: "POST",
      dataType: "json",
      data: {
        action: "cv_load_more_posts",
        nonce: cvBlog.nonce,
        offset: offset,
        perPage: perPage,
      },
    })
      .done(function (response) {
        if (!response || !response.success) {
          return;
        }

        if (response.data.html) {
          $list.append(response.data.html);
          offset = response.data.loaded || offset + perPage;
          $list.data("offset", offset);
        }

        if (!response.data.hasMore) {
          $button.addClass("is-hidden");
        }
      })
      .always(function () {
        setLoading(false);
      });
  }

  $button.on("click", function (event) {
    event.preventDefault();
    loadPosts();
  });
})(jQuery);
