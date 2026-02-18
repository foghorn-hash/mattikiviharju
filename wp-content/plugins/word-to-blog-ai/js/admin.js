jQuery(document).ready(function($) {
    let extractedContent = null;
    
    // Handle file upload
    $('#wtbai-upload-btn').on('click', function() {
        const fileInput = $('#wtbai-word-file')[0];
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Valitse ensin Word-tiedosto');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'wtbai_upload_word');
        formData.append('nonce', wtbaiData.nonce);
        formData.append('word_file', file);
        
        showStatus('Käsitellään Word-tiedostoa...', 'info');
        $(this).prop('disabled', true);
        
        $.ajax({
            url: wtbaiData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    extractedContent = response.data;
                    showContentPreview(extractedContent);
                    showStatus('Tiedosto käsitelty onnistuneesti!', 'success');
                    $('#wtbai-preview-section').slideDown();
                } else {
                    showStatus('Virhe: ' + response.data, 'error');
                }
                $('#wtbai-upload-btn').prop('disabled', false);
            },
            error: function() {
                showStatus('Virhe tiedoston latauksessa', 'error');
                $('#wtbai-upload-btn').prop('disabled', false);
            }
        });
    });
    
    // Handle AI generation
    $('#wtbai-generate-btn').on('click', function() {
        if (!extractedContent) {
            alert('Lataa ensin Word-tiedosto');
            return;
        }
        
        const tone = $('#wtbai-tone').val();
        const contentWithTone = `Tyyli: ${tone}\n\n${extractedContent.text}`;
        
        showStatus('Luodaan blogiartikkelia AI:lla... Tämä voi kestää hetken.', 'info');
        $(this).prop('disabled', true);
        
        $.ajax({
            url: wtbaiData.ajax_url,
            type: 'POST',
            data: {
                action: 'wtbai_process_with_ai',
                nonce: wtbaiData.nonce,
                content: contentWithTone,
                images: JSON.stringify(extractedContent.images || [])
            },
            success: function(response) {
                if (response.success) {
                    showResult(response.data);
                    showStatus('Blogiartikkeli luotu onnistuneesti!', 'success');
                    $('#wtbai-result-section').slideDown();
                } else {
                    showStatus('Virhe: ' + response.data, 'error');
                }
                $('#wtbai-generate-btn').prop('disabled', false);
            },
            error: function() {
                showStatus('Virhe AI-prosessoinnissa', 'error');
                $('#wtbai-generate-btn').prop('disabled', false);
            }
        });
    });
    
    function showStatus(message, type) {
        const $status = $('#wtbai-status');
        $status.removeClass('notice-info notice-success notice-error');
        $status.addClass('notice notice-' + type);
        $status.html('<p>' + message + '</p>');
        $status.slideDown();
    }
    
    function showContentPreview(content) {
        const $preview = $('#wtbai-content-preview');
        const textPreview = content.text.substring(0, 500) + '...';
        $preview.html('<p><strong>Teksti:</strong></p><p>' + textPreview + '</p>');
        
        if (content.images && content.images.length > 0) {
            const $imagesPreview = $('#wtbai-images-preview');
            $imagesPreview.html('<p><strong>Kuvia löydetty:</strong> ' + content.images.length + '</p>');
        }
    }
    
    function showResult(data) {
        const $result = $('#wtbai-result-content');
        $result.html(`
            <p><strong>Otsikko:</strong> ${data.title}</p>
            <p>
                <a href="${data.edit_url}" class="button button-primary" target="_blank">
                    <span class="dashicons dashicons-edit"></span> Muokkaa artikkelia
                </a>
            </p>
            <p class="description">Artikkeli on tallennettu luonnoksena. Voit nyt muokata sitä WordPress-editorissa.</p>
        `);
    }
});
