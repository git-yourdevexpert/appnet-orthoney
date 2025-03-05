Swal.fire({
    title: "Enter group name",
    text: "The group name will be used for the future.",
    input: "text",
    inputPlaceholder: "Enter group name",
    inputAttributes: {
        autocapitalize: "off"
    },
    showCancelButton: false,
    allowOutsideClick: false,
    allowEscapeKey: false,
    allowEnterKey: false,
    confirmButtonText: "Save and Continue",
    showLoaderOnConfirm: true,
    preConfirm: async (groupName) => {
        if (!groupName) {
            Swal.showValidationMessage("Group name is required!");
            return null;
        }

        const form = document.querySelector("#multiStepForm");
        const formData = new FormData(form); // Supports file uploads
        formData.append("action", "orthoney_order_step_process_completed_ajax"); // WordPress AJAX action
        formData.append("group_name", groupName);
        formData.append("currentStep", typeof currentStep !== "undefined" ? currentStep : "");
        formData.append("security", oam_ajax.nonce);

        try {
            const response = await fetch(oam_ajax.ajax_url, {
                method: "POST",
                body: formData,
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const result = await response.json();

            if (!result.success) {
                Swal.showValidationMessage(result.message || "An error occurred");
                return null;
            }

            return result;
        } catch (error) {
            console.error("AJAX error:", error); // Debugging
            Swal.showValidationMessage(`Request failed: ${error.message || "Unknown error"}`);
            return null;
        }
    }
}).then((result) => {
    if (result.isConfirmed && result.value) {
        Swal.fire({
            title: `Group "${result.value.group_name}" created successfully!`,
            text: "Your custom message here.",
            icon: "success"
        });
    }
});