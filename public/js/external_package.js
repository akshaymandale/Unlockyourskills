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
                    <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*" onchange="validateThumbnail(this)">
                    <img id="thumbnailPreview" src="" alt="Thumbnail Preview" style="display:none; max-width: 100px; margin-top: 10px;">
                    <div id="thumbnailFileLink" style="display:none;"></div>
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
                <div class="form-group">
                    <label for="articleUrl">URL <span class="text-danger">*</span></label>
                    <input type="url" class="form-control" id="articleUrl" name="article_url" required>
                </div>
                </div>
                <div class="col-md-6">
                <div class="form-group">
                    <label for="author">Author/Publisher</label>
                    <input type="text" class="form-control" id="author" name = "author">
                </div>
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
                    <input type="file" class="form-control" id="audioFile" name="audio_file" accept=".mp3, .wav">
                    <small class="text-muted">Allowed formats: MP3, WAV</small>
                    <div id="audioFileDisplay" style="margin-top: 10px;"></div>
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
           // $("#externalContentForm")[0].reset(); 

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
            $("#externalModalLabel").text("Add External Content"); // Reset title
            $("#externalTagList").val(""); // Clear hidden input storing tags
            $("#externalTagError").text("").hide(); // Hide any validation errors
            $("#contentType").val(""); // Reset dropdown selection
            $("#dynamicFields").html(""); // Clear dynamically generated fields
           // $("#externalContentForm")[0].reset(); // Reset the entire form
        });

        // ✅ New Fix: Handle Edit Functionality
        $(document).on("click", ".edit-content", function (e) {
            e.preventDefault();
            let contentData = $(this).attr("data-content");
            if (!contentData || contentData === "undefined") {
                console.error("data-content is missing or invalid.");
                return;
            }
            try {
                let parsedData = JSON.parse(contentData);
                $("#external_id").val(parsedData.id);
                $("#title").val(parsedData.title);
                $("#contentType").val(parsedData.content_type).trigger("change");
                $("#versionNumber").val(parsedData.version_number);
                $("#languageSupport").val(parsedData.language_support);
                $("#external_timeLimit").val(parsedData.time_limit);
                $("#external_description").val(parsedData.description);
                $("#externalTagList").val(parsedData.tags);
                $("#videoUrl").val(parsedData.video_url || "");
                $("#courseUrl").val(parsedData.course_url || "");
                $("#platformName").val(parsedData.platform_name || "");
                $("#articleUrl").val(parsedData.article_url || "");
                $("#author").val(parsedData.author || "");
                $("#audioUrl").val(parsedData.audio_url || "");
                $("#speaker").val(parsedData.speaker || "");
        
                $('input[name="mobile_support"][value="' + parsedData.mobile_support + '"]').prop("checked", true);
                $('input[name="audio_source"][value="' + parsedData.audio_source + '"]').prop("checked", true);
        
                
               // ✅ Show previously uploaded thumbnail with correct path
                if (parsedData.thumbnail) {
                    let thumbnailPath = "uploads/external/thumbnails/" + parsedData.thumbnail; // Ensure correct path
                    $("#thumbnailPreview").attr("src", thumbnailPath).show();
                    $("#thumbnailFileLink").html(`<a href="${thumbnailPath}" target="_blank">View Uploaded Thumbnail</a>`).show();
                } else {
                    $("#thumbnailPreview").hide();
                    $("#thumbnailFileLink").hide();
                }

                // ✅ Show previously uploaded audio file
                if (parsedData.audio_file) {
                    $("#audioFileDisplay").html(`Current File: <a href="uploads/audio/${parsedData.audio_file}" target="_blank">${parsedData.audio_file}</a>`).show();
                } else {
                    $("#audioFileDisplay").html("No audio file uploaded.").show();
                }
        
                let tagContainer = document.getElementById("externalTagDisplay");
                tagContainer.innerHTML = "";
                if (parsedData.tags) {
                    parsedData.tags.split(",").forEach(tag => {
                        $("#externalTagDisplay").append(
                            `<span class="tag">${tag.trim()} <span class="remove-tag">&times;</span></span>`
                        );
                    });
                }
        
                // ✅ Ensure Modal Opens
               // $("#externalContentModal").modal({ show: true, backdrop: "static" });
                $("#externalContentModal").modal("show");
        
            } catch (error) {
                console.error("Error parsing JSON:", error);
            }
            document.getElementById("externalModalLabel").textContent = "Edit External Package"; 
        });
        
        // ✅ Ensure Tags Load Properly When Editing
        $("#externalContentModal").on("shown.bs.modal", function () {
            updateTagList(); // Refresh the tag list
        });

    });
});

