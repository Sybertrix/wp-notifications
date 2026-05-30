/* WP Latest Notifications — Admin JS */
jQuery(function($){

    /* ---- Media uploader ---- */
    var mediaFrame;
    $('#open-media-uploader').on('click', function(e){
        e.preventDefault();
        if (mediaFrame) { mediaFrame.open(); return; }
        mediaFrame = wp.media({
            title: 'Select Document',
            button: { text: 'Use this document' },
            library: { type: ['application/pdf','application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'] },
            multiple: false
        });
        mediaFrame.on('select', function(){
            var att = mediaFrame.state().get('selection').first().toJSON();
            $('#notif_doc_url').val(att.url);
            $('#notif_doc_name').val(att.filename || att.title || 'document');
            $('#doc-preview-name').text(att.filename || att.title || 'document');
            $('#doc-preview').show();
        });
        mediaFrame.open();
    });

    $('#clear-doc').on('click', function(){
        $('#notif_doc_url').val('');
        $('#notif_doc_name').val('');
        $('#doc-preview').hide();
    });

    /* ---- Clear link ---- */
    $('#clear-link').on('click', function(){
        $('#notif_link').val('');
    });

});
