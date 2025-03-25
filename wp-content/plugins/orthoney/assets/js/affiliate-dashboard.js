function validateForm(form) {
    let isValid = true;

    form.querySelectorAll("[required]").forEach((input) => {
        let errorMessage = input.nextElementSibling;
        let errorText = input.getAttribute("data-error-message") || "This field is required.";

        if (!errorMessage || !errorMessage.classList.contains("error-message")) {
            return; // Skip if no error message container
        }

        let value = input.value.trim();
        input.style.border = ""; // Reset border
        errorMessage.textContent = ""; // Reset error message

        if (!value) {
            isValid = false;
            errorMessage.textContent = errorText;
        } 
        else if (input.type === "email" && !/^\S+@\S+\.\S+$/.test(value)) {
            isValid = false;
            errorMessage.textContent = "Please enter a valid email address.";
        } 
        else if (input.classList.contains("phone-input")) {
            // Ensure only numbers and exactly 10 digits
            if (!/^\d{10}$/.test(value)) {
                isValid = false;
                errorMessage.textContent = "Please enter a valid 10-digit phone number (numbers only).";
            }
        }

        if (!isValid) {
            input.style.border = "1px solid red";
            errorMessage.style.color = "red";
        }
    });

    return isValid;
}
// Add user
document.addEventListener("DOMContentLoaded", function () {
    let addUserForm = document.getElementById("addUserForm");
    if (addUserForm) {
        addUserForm.setAttribute("novalidate", true);

        addUserForm.addEventListener("submit", function (e) {
            e.preventDefault(); // Prevent default form submission

            if (!validateForm(this)) return; // Stop submission if validation fails

            let formData = new FormData(this);
            formData.append("action", "manage_affiliate_team_member_users"); // WordPress AJAX action
            formData.append("security", oam_ajax.nonce);  // nonce.
            fetch(oam_ajax.ajax_url, {
                method: "POST",
                body: formData,
            })
            .then(response => response.json()) // Parse JSON response
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: data.message || "User created successfully!",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    Swal.fire({
                        title: "Error",
                        text: data.message || "Something went wrong.",
                        icon: "error",
                    });
                }
            })
            .catch(error => {
                console.error("Fetch error:", error); // Log error to console

                Swal.fire({
                    title: "Error",
                    text: "An error occurred while processing the request.",
                    icon: "error",
                });
            });
        });
    }
});

//edit user
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('edit-user-form-btn')) {
        event.preventDefault();

        const target = event.target;
        const userTr = target.closest('tr');

        if(userTr){
            const userid = userTr.getAttribute('data-userid');

            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_affiliate_team_member_by_base_id',
                    id: userid,
                    security: oam_ajax.nonce,// nonce.
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                
                    const userid = data.data.userid;
                    const first_name = data.data.first_name;
                    const last_name = data.data.last_name;
                    const email = data.data.email;
                    const phone = data.data.phone;
                    const affiliate_type = data.data.affiliate_type;
                 
                    const form = document.querySelector('#edit-user-form form');
                    form.querySelector('#user_id').value = userid;
                    form.querySelector('#first_name').value = first_name;
                    form.querySelector('#last_name').value = last_name;
                    const emailField = form.querySelector('#email');emailField.value = email; emailField.readOnly = true;
                    form.querySelector('#phone').value = phone;
                    form.querySelector('#affiliate_type').value = affiliate_type;
        

                    setTimeout(function() {
                        lity(event.target.getAttribute('data-popup'));
                    }, 250);
                    
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Failed to get User.',
                        icon: 'error',
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while removing the User.',
                    icon: 'error',
                });
            });
        }else{
            lity(event.target.getAttribute('data-popup'));
        }
        
    }
    
});

//delete user
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('delete-user')) {
        event.preventDefault();

        const target = event.target;
        const userTr = target.closest('tr');
        const userID = userTr?.getAttribute('data-userid');

        if (!userID) {
            Swal.fire({
                title: 'Error',
                text: 'User ID not found.',
                icon: 'error',
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you really want to remove this User?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, remove!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'deleted_affiliate_team_member',
                        id: userID,
                        security: oam_ajax.nonce,// nonce.
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'The user has been removed successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });

                        // Remove row smoothly instead of full page reload
                        setTimeout(() => {
                            userTr.remove();
                        }, 1500);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to remove user.',
                            icon: 'error',
                        });
                    }
                })
                .catch((error) => {
                    console.error("Delete Error:", error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while removing the user.',
                        icon: 'error',
                    });
                });
            }
        });
    }
});

//Change Affiliate Admin user
// TODO: Add Sweet Alert Pending
//Edit my profile 
document.addEventListener('click', function (event) {
    if (event.target.id === 'save-profile') {
        event.preventDefault();
        if (!validateForm(this)) return; // Stop submission if validation fails
        const form = document.querySelector('#affiliate-profile-form');
        const formData = new FormData(form);
        formData.append('action', 'update_affiliate_profile');
        formData.append("security", oam_ajax.nonce);  // nonce.

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })

        .then(response => response.json())
        .then(data => {
            const messageDiv = document.querySelector('#profile-message');
            if (data.success) {
                Swal.fire({
                    title: data.message || "Affiliate Profile updated successfully!",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.message || "Something went wrong. Please try again",
                    icon: "error",
                });
            }
        })
        .catch(() => {
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while updating the Affiliate profile.',
                icon: 'error',
            });
        });
    }
});

//Edit my profile

//Change Affiliate Admin user
document.addEventListener("DOMContentLoaded", function() {
    const changeRoleBtn =  document.getElementById("changeRoleBtn");
    if(changeRoleBtn){
    document.getElementById("changeRoleBtn").addEventListener("click", function(event) {
        event.preventDefault();
        var userDropdown = document.getElementById("userDropdown");
        var selectedUserId = userDropdown.value;

        console.log("Selected User ID:", selectedUserId);

        if (!selectedUserId) {
            alert("Please select a user.");
            return;
        }

        let formData = new FormData();
        formData.append("action", "change_user_role_logout"); // WordPress AJAX action
        formData.append("security", oam_ajax.nonce);  // nonce.
        formData.append("selected_user_id", selectedUserId);

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json()) // Directly parse JSON
        .then(data => {
            console.log("Parsed JSON:", data);
            if (data.success) {
                alert(data?.message || "Role changed...");
                window.location.reload();
            } else {
                alert(data.data?.message || "Error occurred!");
            }
        })
        .catch(error => {
            console.error("AJAX Error:", error);
            alert("Something went wrong. Please try again.");
        });
    });
    }
});