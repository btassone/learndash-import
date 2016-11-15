var runImportBtn = document.getElementById("run-import");
var runDeleteAllDataBtn = document.getElementById("delete-all-data");
var runBackBtn = document.getElementById("go-back-to-main");

var file_frame;

if(runImportBtn)
    runImportBtn.addEventListener('click', runImport);

if(runDeleteAllDataBtn)
    runDeleteAllDataBtn.addEventListener('click', runDeleteAllData);

if(runBackBtn)
    runBackBtn.addEventListener('click', runBack);

function runDeleteAllData() {
    if(confirm("Are you sure you want to delete all the LearnDash Course / Quiz / Question data?"))
        window.location.href = "/wp-admin/admin.php?page=learndash-import&delete=true";
}

function runBack() {
    window.location.href = "/wp-admin/admin.php?page=learndash-import";
}

function runImport() {
    // If the media frame already exists, reopen it.
    if (file_frame) {
        file_frame.open();
        return;
    }

    // Create the media frame.
    file_frame = wp.media.frames.file_frame = wp.media({
        frame: 'select',
        button: {
            text: "Add Course CSV File"
        },
        multiple: false,
        library: {
            type: ['application/json']
        }
    });

    // When an image is selected, run a callback.
    file_frame.on('select', function () {

        // We set multiple to false so only get one image from the uploader
        var attachment = file_frame.state().get('selection').first().toJSON();
        var url = attachment.url;

        var hiddenForm = document.getElementById("hidden-submit-form");
        var hiddenUrlField = document.getElementById("hidden-url-field");

        hiddenUrlField.setAttribute("value", url);
        hiddenForm.setAttribute("action", "/wp-admin/admin.php?page=learndash-import&run=true");
        hiddenForm.submit();
    });

    // Finally, open the modal
    file_frame.open();
}