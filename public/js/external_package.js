function toggleContentFields() {
    let contentType = document.getElementById("contentType").value;
    let dynamicFields = document.getElementById("dynamicFields");
    let fields = "";

    if (contentType === "youtube-vimeo") {
        fields += `
            <div class="row">
                <div class="col-md-6">
                <div class="form-group">
                    <label for="videoUrl">Video URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="videoUrl" name="video_url" required>
                </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">
                    <label for="thumbnail">Thumbnail Preview</label>
                    <input type="file" class="form-control" id="thumbnail" name = "thumbnail">
                </div>
                </div>
            </div>
        `;
    } else if (contentType === "linkedin-udemy") {
        fields += `
            <div class="row">
                <div class="col-md-6">
                <div class="form-group">
                    <label for="courseUrl">Course URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="courseUrl" name = "course_url" required>
                </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">
                    <label for="platformName">Platform Name <span class="text-danger">*</span></label>
                    <select class="form-control" id="platformName" name="platform_name" required>
                        <option value="">Select</option>
                        <option value="LinkedIn Learning">LinkedIn Learning</option>
                        <option value="Udemy">Udemy</option>
                        <option value="Coursera">Coursera</option>
                    </select>
                </div>
                </div>
            </div>
        `;
    } else if (contentType === "web-links-blogs") {
        fields += `
            <div class="row">
                <div class="col-md-6">
                    <label for="articleUrl">URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="articleUrl" name="article_url" required>
                </div>
                <div class="col-md-6">
                    <label for="author">Author/Publisher</label>
                    <input type="text" class="form-control" id="author" name = "author">
                </div>
            </div>
        `;
    } else if (contentType === "podcasts-audio") {
        fields += `
          <div class="row">
                <div class="col-md-6">
                <div class="form-group">
                    <label for="audioSource">Audio Source <span class="text-danger">*</span></label>
                    <select class="form-control" id="audioSource" name = "audio_source" required onchange="toggleAudioFields()">
                        <option value="upload">Upload File</option>
                        <option value="url">Audio URL</option>
                    </select>
                </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">
                    <label for="speaker">Speaker / Host</label>
                    <input type="text" class="form-control" id="speaker" name = "speaker">
                </div>
                </div>
            </div>
            <div class="row mt-3" id="audioUploadField">
                <div class="col-md-6">
                <div class="form-group">
                    <label for="audioFile">Upload Audio (MP3/WAV) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="audioFile"  name = "audio_file" accept=".mp3, .wav">
                    <small class="text-muted">Allowed formats: MP3, WAV</small>
                </div>
                </div>
            </div>
            <div class="row mt-3" id="audioUrlField" style="display: none;">
                <div class="col-md-12">
                <div class="form-group">
                    <label for="audioUrl">Audio URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="audioUrl" name = "audio_url">
                </div>
                </div>
            </div>
        `;
    }

    dynamicFields.innerHTML = fields;
    // ✅ Re-attach validation to new fields
    attachValidation();
}

function toggleAudioFields() {
    let audioSource = document.getElementById("audioSource").value;
    document.getElementById("audioUploadField").style.display = audioSource === "url" ? "none" : "flex";
    document.getElementById("audioUrlField").style.display = audioSource === "url" ? "flex" : "none";
}

// External Modal Tags Functionality
document.addEventListener("DOMContentLoaded", function () {
    const externalTagInput = document.getElementById("externalTagInput");
    const externalTagContainer = document.getElementById("externalTagDisplay");
    const externalHiddenTagList = document.getElementById("externalTagList");
    const externalTagError = document.getElementById("externalTagError");
    const externalContentForm = document.getElementById("externalContentForm");

    $(document).ready(function () {
        function updateTagList() {
            let tagsArray = [];
            $("#externalTagDisplay .tag").each(function () {
                tagsArray.push($(this).text().replace(" ×", "").trim());
            });
            $("#externalTagList").val(tagsArray.join(",")); // Store tags as comma-separated values
        }

        // Add Tag on Enter
        $("#externalTagInput").keypress(function (e) {
            if (e.which === 13) {
                e.preventDefault();
                let tag = $(this).val().trim();
                if (tag !== "" && !isDuplicateTag(tag)) {
                    $("#externalTagDisplay").append(
                        `<span class="tag">${tag} <span class="remove-tag">&times;</span></span>`
                    );
                    updateTagList();
                    $(this).val(""); // Clear input
                    $("#externalTagError").text("").hide();
                } else {
                    $("#externalTagError").text("Duplicate or empty tag is not allowed.").show();
                }
            }
        });

        // Remove Tag
        $(document).on("click", ".remove-tag", function () {
            $(this).parent().remove();
            updateTagList();
            checkTagValidation();
        });

        function isDuplicateTag(tag) {
            let isDuplicate = false;
            $("#externalTagDisplay .tag").each(function () {
                if ($(this).text().replace(" ×", "").trim() === tag) {
                    isDuplicate = true;
                    return false;
                }
            });
            return isDuplicate;
        }

        function checkTagValidation() {
            let tags = $("#externalTagList").val().trim();
            if (tags === "") {
                $("#externalTagError").text("Tags/Keywords are required.").show();
                return false;
            } else {
                $("#externalTagError").text("").hide();
                return true;
            }
        }

        // Validate on Focus Out
        $("#externalTagInput").focusout(function () {
            checkTagValidation();
        });

        // Validate on Submit
        $("#externalContentForm").submit(function (e) {
            let isValid = checkTagValidation();
            if (!isValid) {
                e.preventDefault(); // Prevent form submission
            }
        });

       
        // Clear validation errors when modal opens
        $("#externalContentModal").on("show.bs.modal", function () {
            $("#externalTagError").text("").hide();

            // Reset the form fields
            $("#externalContentForm")[0].reset(); 

            // Manually clear select dropdowns and text areas
            $("#content_type, #mobile_support, #audio_source").val("").trigger("change");
            
            // Clear hidden fields and file inputs
            $("#video_url, #thumbnail, #course_url, #platform_name, #article_url, #author, #audio_url, #speaker").val("");
            $("#audio_file").val(null);

            // Clear validation errors (if applicable)
            $(".error-message").text("");
        });

        // Clear previous content type and tags when modal closes
        $("#externalContentModal").on("hidden.bs.modal", function () {
            $("#externalTagDisplay").empty(); // Remove all tags
            $("#externalTagList").val(""); // Clear hidden input storing tags
            $("#externalTagError").text("").hide(); // Hide any validation errors
            $("#contentType").val(""); // Reset dropdown selection
            $("#dynamicFields").html(""); // Clear dynamically generated fields
            $("#externalContentForm")[0].reset(); // Reset the entire form
        });
    });
});
