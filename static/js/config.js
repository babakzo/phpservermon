$(function() {
  $('[data-toggle-target]').on('click', function (event) {
    var $checkbox = $(event.currentTarget);
    var $targetId = $checkbox.data('toggle-target');

    if ($checkbox.is(':checked')) {
      $($targetId).show();
    } else {
      $($targetId).hide();
    }
  })
});