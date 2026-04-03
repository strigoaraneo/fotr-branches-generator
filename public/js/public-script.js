jQuery(document).ready(function ($) {
  // Get Directions Popup Handler
  var $popup = $("#branch-directions-popup");

  if ($popup.length) {
    // Hide popup when clicking close button
    $(".directions-popup-close").on("click", function (e) {
      e.preventDefault();
      $popup.addClass("popup-hidden");
    });

    // Hide popup when clicking outside of it
    $popup.on("click", function (e) {
      if (e.target === this) {
        $(this).addClass("popup-hidden");
      }
    });

    // Show popup when clicking Get Directions links
    $(".get-directions-btn .btn, .cta-buttons .btn-outline").on(
      "click",
      function (e) {
        e.preventDefault();
        $popup.removeClass("popup-hidden");
        $popup.find(".directions-popup-content").focus();
      },
    );

    // Prevent closing when clicking inside the popup content
    $(".directions-popup-content").on("click", function (e) {
      e.stopPropagation();
    });
  }

  // Optional: Close popup with Escape key
  $(document).on("keydown", function (e) {
    if (e.keyCode === 27 && !$popup.hasClass("popup-hidden")) {
      $popup.addClass("popup-hidden");
    }
  });
});
