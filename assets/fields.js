(function($){
  $(function(){
    $("[data-ft-fields-action='open-media']").click(function(){
      var attach = wp.media.editor.send.attachment;
      var target = $('#' + $(this).data('ft-fields-target'));

      wp.media.editor.send.attachment = function(props, attachment) {
        target.val(attachment.url);
        wp.media.editor.send.attachment = attach;
      }

      wp.media.editor.open();

      return false; 
    });
  });
}(jQuery));