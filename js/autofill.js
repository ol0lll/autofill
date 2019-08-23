(function ($, Drupal) {

  Drupal.behaviors.autofillFromAnotherField = {
    attach: function (context, settings) {
      if (typeof settings.autofill.field_mapping !== 'undefined') {
        var field_mapping = settings.autofill.field_mapping;
        for (var target_field in field_mapping) {
          var source_field = field_mapping[target_field];

          var $source_field = $('[name="' + source_field + '[0][value]"]', context);
          var $target_field = $('[name="' + target_field + '[0][value]"]', context);
          var target_field_was_manipulated = false;

          // Only process if source field and target field are present.
          if ($source_field.length > 0 && $target_field.length > 0) {
            // Automatically fill target field with value of the source
            // field, when it's empty or values are identical.
            if (!$target_field.val() || $source_field.val() === $target_field.val()) {
              var unique_process_name = 'autofill_' + target_field;
              $source_field.once(unique_process_name).on('input', function () {
                // Autofill the target field only when it was not manipulated
                // before.
                if (!target_field_was_manipulated) {
                  $target_field.val($source_field.val());
                  // Trigger input event, to fire additional events, like
                  // length indicator.
                  $target_field.trigger('input');
                }
              });
            }
            else {
              target_field_was_manipulated = true;
            }

            // Store, when target field was manipulated manually. Then we
            // should not process the autofill again.
            $target_field.on('keypress', function () {
              target_field_was_manipulated = true;
            });
          }
        }
      }
    }
  };

})(jQuery, Drupal);
