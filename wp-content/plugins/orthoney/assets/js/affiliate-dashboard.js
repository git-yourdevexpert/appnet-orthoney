//Edit my profile js start
// TODO
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("save-profile").addEventListener("click", function() {
        let formData = {
            action: "save_affiliate_profile",
            first_name: document.getElementById("first_name").value,
            last_name: document.getElementById("last_name").value,
            billing_phone: document.getElementById("billing_phone").value,
            billing_email: document.getElementById("billing_email").value,
        };

        fetch(affiliateProfileAjax.ajax_url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams(formData),
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById("profile-message").innerHTML = data;
            });
    });
});
//Edit my profile js end
// TODO

//add user js start

document.getElementById("addUserForm").addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    formData.append("action", "create_new_user"); // WordPress AJAX action

    fetch(affiliateProfileAjax.ajax_url, {
        method: "POST",
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        let userMessage = document.getElementById("user-message");
        userMessage.innerHTML = data.message;

        if (data.success) {
            userMessage.style.color = "green"; // Success message color
            this.reset(); // Reset form after successful submission
        } else {
            userMessage.style.color = "red"; // Error message color
        }
    })
    .catch(error => {
        console.error("Error:", error);
        document.getElementById("user-message").innerHTML = "An error occurred. Please try again.";
    });
});

//add user js end


document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".edit-user").forEach(function (editButton) {
        editButton.addEventListener("click", function (event) {
            event.preventDefault();

            let userId = this.getAttribute("data-id");
            let userName = this.getAttribute("data-name");
            let userEmail = this.getAttribute("data-email");
            let userPhone = this.getAttribute("data-phone");
            let userRole = this.getAttribute("data-role");

            document.getElementById("edit-name").value = userName;
            document.getElementById("edit-email").value = userEmail;
            document.getElementById("edit-phone").value = userPhone;
            document.getElementById("edit-role").value = userRole;

            document.getElementById("save-user").setAttribute("data-id", userId);
        });
    });

    document.getElementById("save-user").addEventListener("click", function () {
        let userId = this.getAttribute("data-id");
        let updatedName = document.getElementById("edit-name").value;
        let updatedEmail = document.getElementById("edit-email").value;
        let updatedPhone = document.getElementById("edit-phone").value;
        let updatedRole = document.getElementById("edit-role").value;

        let formData = new FormData();
        formData.append("action", "update_affiliate_user");
        formData.append("user_id", userId);
        formData.append("name", updatedName);
        formData.append("email", updatedEmail);
        formData.append("phone", updatedPhone);
        formData.append("role", updatedRole);
        formData.append("security", ajax_object.security); // Nonce for security

        fetch(ajax_object.ajax_url, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("User updated successfully!");
                location.reload(); // Refresh to see updated data
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(error => console.error("Error:", error));
    });
});

//

