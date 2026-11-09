(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      $("#edit-moderation-state-0-state", context).change(function(){
        if ($(this).val() === 'unpublished') {
          $('#edit-field-motifs-de-depublication-wrapper').show();
        }
      });
      if ($("#edit-moderation-state-0-state").val() !== 'unpublished') {
        $("#edit-field-motifs-de-depublication-wrapper").hide();
      }

    }};
})(jQuery, Drupal);
