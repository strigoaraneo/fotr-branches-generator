jQuery(document).ready(function ($) {
  // Media Upload Handler
  var mediaUploader;

  $("#upload_image_button").on("click", function (e) {
    e.preventDefault();

    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    mediaUploader = wp.media({
      title: "Select Branch Hero Image",
      button: {
        text: "Use this image",
      },
      multiple: false,
    });

    mediaUploader.on("select", function () {
      var attachment = mediaUploader.state().get("selection").first().toJSON();
      $("#branch_image_id").val(attachment.id);
      $("#branch_image_preview").html(
        '<img src="' +
          attachment.url +
          '" style="max-width:200px; height:auto; display:block; border:1px solid #ccc;">',
      );
      $("#remove_image_button").show();
    });

    mediaUploader.open();
  });

  $("#remove_image_button").on("click", function (e) {
    e.preventDefault();
    $("#branch_image_id").val("");
    $("#branch_image_preview").empty();
    $(this).hide();
  });

  // Delete Branch
  $(".cbg-delete-branch").on("click", function (e) {
    e.preventDefault();

    if (
      !confirm(
        "Are you sure you want to delete this branch? Associated programs and services will also be deleted.",
      )
    ) {
      return;
    }

    var $btn = $(this);
    var branchId = $btn.data("branch-id");
    var pageId = $btn.data("page-id");

    $.ajax({
      url: cbgAdmin.ajaxurl,
      type: "POST",
      data: {
        action: "cbg_delete_branch",
        nonce: cbgAdmin.nonce,
        branch_id: branchId,
        page_id: pageId,
      },
      success: function (response) {
        if (response.success) {
          $btn.closest("tr").fadeOut(300, function () {
            $(this).remove();
          });
          alert("Branch deleted successfully.");
          location.reload();
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Error deleting branch");
      },
    });
  });

  // Delete Service
  $(".cbg-delete-service").on("click", function (e) {
    e.preventDefault();

    if (!confirm("Are you sure you want to delete this service?")) {
      return;
    }

    var $btn = $(this);
    var serviceId = $btn.data("service-id");
    var branchId = $btn.data("branch-id");

    $.ajax({
      url: cbgAdmin.ajaxurl,
      type: "POST",
      data: {
        action: "cbg_delete_service",
        nonce: cbgAdmin.nonce,
        service_id: serviceId,
        branch_id: branchId,
      },
      success: function (response) {
        if (response.success) {
          $btn.closest("tr").fadeOut(300, function () {
            $(this).remove();
          });
          alert("Service deleted successfully");
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Error deleting service");
      },
    });
  });

  // Delete Program
  $(".cbg-delete-program").on("click", function (e) {
    e.preventDefault();

    if (!confirm("Are you sure you want to delete this program?")) {
      return;
    }

    var $btn = $(this);
    var programId = $btn.data("program-id");
    var branchId = $btn.data("branch-id");

    $.ajax({
      url: cbgAdmin.ajaxurl,
      type: "POST",
      data: {
        action: "cbg_delete_program",
        nonce: cbgAdmin.nonce,
        program_id: programId,
        branch_id: branchId,
      },
      success: function (response) {
        if (response.success) {
          $btn.closest("tr").fadeOut(300, function () {
            $(this).remove();
          });
          alert("Program deleted successfully.");
        } else {
          alert("Error: " + response.data);
        }
      },
      error: function () {
        alert("Error deleting program");
      },
    });
  });
});
