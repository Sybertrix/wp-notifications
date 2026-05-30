/* =========================================================
   WP Latest Notifications — Functional Admin Actions JS
   ========================================================= */

jQuery(function($){

    var mediaFrame

    // Media library file attachment trigger engine[cite: 2]
    $('#open-media-uploader').on('click', function(e){
        e.preventDefault()
        
        if (mediaFrame) { 
            mediaFrame.open()
            return 
        }
        
        mediaFrame = wp.media({
            title: 'Select Verification Document Asset Node',
            button: { text: 'Assign File Asset to Frame' },
            library: { type: [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ] },
            multiple: false
        })
        
        mediaFrame.on('select', function(){
            var attachment = mediaFrame.state().get('selection').first().toJSON()
            $('#notif_doc_url').val(attachment.url)
            $('#notif_doc_name').val(attachment.filename || attachment.title || 'document')
            $('#doc-preview-name').text(attachment.filename || attachment.title || 'document')
            $('#doc-preview').show()
        })
        
        mediaFrame.open()
    })

    // Wipe active media attachment selection parameters[cite: 2]
    $('#clear-doc').on('click', function(){
        $('#notif_doc_url').val('')
        $('#notif_doc_name').val('')
        $('#doc-preview').hide()
    })

    // Clear target external link data values[cite: 2]
    $('#clear-link').on('click', function(){
        $('#notif_link').val('')
    })

})
