function formatText(command, value = null) {
    document.execCommand(command, false, value);
    document.getElementById('editor').focus();
}

document.getElementById('formatBlock').addEventListener('change', function() {
    formatText('formatBlock', this.value);
});

//Autosave
$(document).ready(function() {
    let autoSaveTimer;
    let isSaving = false;
    const editor = document.getElementById('editor');
    const statusElement = $('#status');
    
    if (editor.contentEditable === 'true') {
        editor.addEventListener('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(autoSaveContent, 1000); 
        });
    }
    
    function autoSaveContent() {
        if (isSaving) return;
        
        isSaving = true;
        statusElement.text('Saving...').css('color', 'blue');
        
        const content = editor.innerHTML;
        const documentId = $('#editor').data('document-id');

        $.ajax({
            url: '../core/handleForms.php',
            type: 'POST',
            data: { 
                content: content,
                document_id: documentId 
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    statusElement.text('Saved at ' + new Date().toLocaleTimeString()).css('color', 'green');
                } else {
                    statusElement.text('Error: ' + (response.message || 'Unknown error')).css('color', 'red');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Error saving';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMsg = response.message;
                    }
                } catch (e) {
                }
                statusElement.text(errorMsg).css('color', 'red');
            },
            complete: function() {
                isSaving = false;
            }
        });
    }

    editor.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'b':
                    e.preventDefault();
                    formatText('bold');
                    break;
                case 'i':
                    e.preventDefault();
                    formatText('italic');
                    break;
                case 'u':
                    e.preventDefault();
                    formatText('underline');
                    break;
            }
        }
    });
});