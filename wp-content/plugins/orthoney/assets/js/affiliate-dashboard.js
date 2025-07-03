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
            // Allow formats like (818) 974-1070 or 8189741070
            let digitsOnly = value.replace(/\D/g, '');
            if (digitsOnly.length !== 10) {
                isValid = false;
                errorMessage.textContent = "Please enter a valid 10-digit phone number (e.g., (818) 974-1070).";
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
        addUserForm.reset();
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
    if (event.target.classList.contains('addnewaffiliateteammember')) {
        const form = document.querySelector('#edit-user-form form');
        form.reset();
        form.querySelector('#user_id').value = '';
        jQuery("#affiliate_type").val(null).trigger('change');
        const emailField = form.querySelector('#email');
        emailField.readOnly = false;
    }
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
                    const form = document.querySelector('#edit-user-form form');
                    form.reset();
                
                    const userid = data.data.userid;
                    const first_name = data.data.first_name;
                    const last_name = data.data.last_name;
                    const email = data.data.email;
                    const phone = data.data.phone;
                    const affiliate_type = data.data.affiliate_type;
                 
                    form.querySelector('#user_id').value = userid;
                    form.querySelector('#first_name').value = first_name;
                    form.querySelector('#last_name').value = last_name;
                    const emailField = form.querySelector('#email');emailField.value = email; 
                    emailField.readOnly = true;
                    form.querySelector('#phone').value = phone;
                    form.querySelector('#affiliate_type').value = affiliate_type;
        

                    setTimeout(function() {
                         jQuery("#affiliate_type").select2({
                            placeholder: "Please select a type",
                            allowClear: false
                        });
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
            text: 'Do you really want to remove this team member?',
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
                            text: 'The team member has been removed successfully.',
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
        const form = document.querySelector('#affiliate-profile-form');
        if (!validateForm(form)) return; // Stop submission if validation fails

        process_group_popup();
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
                    title: data.message || "Your profile has been updated successfully.",
                    icon: "success",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                });
                setTimeout(function () {
                  window.location.reload();
                }, 750);
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


    if (event.target.id === 'save-remittance') {
        event.preventDefault();
        const form = document.querySelector('#affiliate-remittance-form');
        if (!validateForm(form)) return; // Stop submission if validation fails
        
        process_group_popup();

        const formData = new FormData(form);
        formData.append('action', 'update_affiliate_remittance');
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
                    title: data.message || "Your remittance has been updated successfully.",
                    icon: "success",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                });
                setTimeout(function () {
                  window.location.reload();
                }, 750);
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
    if (event.target.id === 'affiliate-product-price-profile') {
        
        event.preventDefault();
    
        process_group_popup();
        const form = document.querySelector('#affiliate-update-price-form');
        const formData = new FormData(form);

        // Correct way to access form data
        const selling_minimum_price = parseFloat(formData.get('selling_minimum_price'));
        const product_price = parseFloat(formData.get('product_price'));

        if (selling_minimum_price > product_price) {
            Swal.close();
            Swal.fire({
                title: "Organization selling price cannot be lower than the product's standard selling price.",
                icon: "error",
                
            });
            return;
        }
        process_group_popup();

        formData.append('action', 'update_price_affiliate_profile');
        formData.append("security", oam_ajax.nonce);  // nonce.

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: data.message || "Updated price successfully!",
                    icon: "success",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                });
                 setTimeout(function () {
                  window.location.reload();
                }, 750);

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

    if (event.target.id === 'affiliate-gift-card-profile') {
        
        event.preventDefault();
       

        const form = document.querySelector('#affiliate-gift-card-form');
        const formData = new FormData(form);
        process_group_popup();

        formData.append('action', 'update_gift_card_profile');
        formData.append("security", oam_ajax.nonce);  // nonce.

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: data.message || "Updated gift card successfully!",
                    icon: "success",
                    timer: 2000,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                });
                 setTimeout(function () {
                  window.location.reload();
                }, 750);

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

    if (event.target.id === 'affiliate-mission-statement-profile') {
        
        event.preventDefault();
       

        const form = document.querySelector('#affiliate-mission-statement-form');
        const formData = new FormData(form);
  
        process_group_popup();

        formData.append('action', 'update_mission_statement_profile');
        formData.append("security", oam_ajax.nonce);  // nonce.

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })

        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: data.message || "Updated mission statement successfully!",
                    icon: "success",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                    showConfirmButton: false,
                });
                 setTimeout(function () {
                  window.location.reload();
                }, 750);

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
    const changeRoleBtn = document.getElementById("changeRoleBtn");
    
    if (changeRoleBtn) {
        changeRoleBtn.addEventListener("click", function(event) {
            event.preventDefault();
            // process_group_popup();

            const userDropdown = document.getElementById("userDropdown");
            const selectedUserId = userDropdown?.value;

            console.log("Selected User ID:", selectedUserId);

            if (!selectedUserId) {
                Swal.fire({
                    title: 'Error',
                    text: 'Please select a team member.',
                    icon: 'error',
                });
                return;
            }
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you really want to change permission?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, I do',
                cancelButtonText: 'No, I do not',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    process_group_popup();
                    let formData = new FormData();
                    formData.append("action", "change_user_role_logout"); // WordPress AJAX action
                    formData.append("security", oam_ajax?.nonce || "");  // Ensure nonce is available
                    formData.append("selected_user_id", selectedUserId);

                    fetch(oam_ajax?.ajax_url || "", {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json()) // Directly parse JSON
                    .then(data => {
                        console.log("Parsed JSON:", data);
                        if (data.success) {
                            Swal.fire({
                                title: 'Change permission successfully.',
                                text: '',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false,
                                timerProgressBar: true
                            });

                            setTimeout(() => {
                                const userTr = userDropdown.closest("tr"); // Adjust based on your HTML structure
                                if (userTr) userTr.remove();
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: data.data?.message || 'An error occurred while changing permission.',
                                icon: 'error',
                            });
                        }
                    })
                    .catch(error => {
                        console.error("AJAX Error:", error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Something went wrong. Please try again.',
                            icon: 'error',
                        });
                    });
                }
            });
        });
    }
});


//Edit Sales Representative Profile 
document.addEventListener('click', function (event) {
    if (event.target.id === 'sales-rep-save-profile') {
        event.preventDefault();
        if (!validateForm(document.getElementById('sales-rep-profile-form'))) return;

        const form = document.getElementById('sales-rep-profile-form');
        const formData = new FormData(form);
        formData.append('action', 'update_sales_representative');
        formData.append('security', oam_ajax?.nonce || '');

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: data.message || 'Sales Representative Profile updated successfully!',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    timerProgressBar: true
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Something went wrong. Please try again.',
                    icon: 'error',
                });
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while updating the Sales Representative profile.',
                icon: 'error',
            });
        });
    }
});


//Switching Sales Rep to Customer or Organization
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('customer-login-btn') || event.target.classList.contains('organization-login-btn')) {
        event.preventDefault();
        process_group_popup();

        const userid = event.target.getAttribute('data-user-id');

        if (!userid) {
            Swal.fire({
                title: 'Error',
                text: 'Missing user data. Please try again.',
                icon: 'error',
            });
            return;
        }

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'auto_login_request_to_sales_rep',
                user_id: userid,
                security: oam_ajax?.nonce || ''
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.url) {
                console.log(decodeURIComponent(data.url));
                window.location.href = decodeURIComponent(data.url); // Perform the redirect
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Failed to generate login URL.',
                    icon: 'error',
                });
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while generating the login URL.',
                icon: 'error',
            });
        });
    }
});

//Hide default user switching button
document.addEventListener('DOMContentLoaded', function () {
    const switchBackLink = document.querySelector('.woocommerce-MyAccount-navigation-link--user-switching-switch-back');
    if (switchBackLink) {
        switchBackLink.remove();
    }
});

//After sent email showing message
document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    const action = urlParams.get('action');
    const token = urlParams.get('token');
  
    if (action === 'organization-link' && token) {
      console.log('Valid request detected, sending AJAX...');
    
      fetch(oam_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'add_affiliate_link',
          security: oam_ajax.nonce,
          token: token,
        }),
      })
      .then(response => response.json())
      .then(data => {
        Swal.fire({
          title: data.message || 'Link successfully!',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false,
          timerProgressBar: true
        }).then(() => {
          // Remove query parameters and refresh
          const cleanUrl = window.location.origin + window.location.pathname;
          window.history.replaceState(null, '', cleanUrl);
          window.location.reload();
        });
      })
      .catch(error => {
        console.error('Fetch Error:', error);
      });
    } else {
      console.warn('Invalid or missing action/token.');
    }
  });

//Link Customer by email code
const searchbutton = document.querySelector('#search-button');
if(searchbutton){
document.getElementById('search-button').addEventListener('click', async function () {
    const emailInput = document.getElementById('customer-email-search');
    const form = emailInput.closest('form') || document.body;

    if (!validateForm(form)) {
        return;
    }

    process_group_popup();
    const email = emailInput.value.trim();
    const params = new URLSearchParams({
        action: 'search_customer_by_email',
        email: email,
        security: oam_ajax?.nonce || ''
    });

    try {
        const response = await fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: params,
        });

        const data = await response.json();
        const resultsList = document.getElementById('customer-email-results');
        resultsList.innerHTML = '';

        if (data.success && data.customers.length > 0) {
            $exist_status = 0;
            let  title = 'Customer has been found';
            let html = `Send Request to Customer?`
            html += "<div class='exceptions'><strong>Customer Details: </strong><ul>";

            let user_id = 0;
            data.customers.forEach(customer => {
                user_id = customer.id;
                $exist_status = customer.exist_status;
                title = customer.message;
                html += `<li><span>Name: </span> ${customer.name}</li>`;
                html += `<li><span>Email: </span> ${customer.email}</li>`;
            });

            html += "</ul></div>";
            
            if ($exist_status != 2) {
                html = "";
            }
            if ($exist_status == -1) {
                html = 'Customer has already blocked.';
            }
            if ($exist_status == 1) {
                html = 'Customer has already linked.';
            }

            const result = await Swal.fire({
                title: html,
                showCancelButton: true,
                showConfirmButton: false,
                showDenyButton: false,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                denyButtonColor: "#3085d6",
                confirmButtonText: "Yes",
                cancelButtonText: "Cancel",
                denyButtonText: "Send Request",
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                reverseButtons: true,
                willOpen: () => {
                    if ($exist_status == 1 || $exist_status == -1) { 
                        Swal.getCancelButton().textContent = "Okay, Got It"; 
                    }
                    if ($exist_status == 2) {
                      Swal.getConfirmButton().style.display = "inline-block";
                    //   Swal.getCancelButton().style.display = "inline-block";
                    }
                
                    if ($exist_status == 0) {
                        // Swal.getCancelButton().style.display = "inline-block";
                      Swal.getDenyButton().style.display = "inline-block";
                    }
                  },
            });

            if (result.isConfirmed || result.isDenied) {
                process_group_popup();
                // AJAX request to store the entry in the database
                const requestParams = new URLSearchParams({
                    action: 'add_affiliate_request',
                    customer_id: user_id,
                    security: oam_ajax?.nonce || ''
                });

                const addResponse = await fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    body: requestParams,
                });

                const addData = await addResponse.json();

                if (addData.success) {
                    Swal.fire({
                        title: "Success",
                        text: addData.message,
                        icon: "success",
                        showConfirmButton: false,
                    });
                    setTimeout(function () {
                        window.location.reload();
                      }, 1000);
                } else {
                    Swal.fire({
                        title: "Error",
                        text: addData.message,
                        icon: "error",
                    });
                }
            }
           
        } else {
            Swal.fire({
                title: 'Customer not found. Please try another email.',
                icon: "error",
                timer: 2500,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: "Error",
            text: "An error occurred while processing the request.",
            icon: "error",
        });
    }
});
}
//Resend email button code 
document.addEventListener('click', async function (event) {
    if (event.target.classList.contains('resend-email-btn')) {
        const customerId = event.target.getAttribute('data-customer-id');

        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to resend the email?",
            icon: 'question',
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            showCancelButton: true,
            confirmButtonText: 'Yes, Resend it!',
            cancelButtonText: 'No, Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: true,
        }).then(async (result) => {
            if (result.isConfirmed) {
                process_group_popup();
                try {
                    const response = await fetch(oam_ajax.ajax_url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'add_affiliate_request',
                            customer_id: customerId,
                            security: oam_ajax?.nonce || ''
                        }),
                    });

                    const data = await response.json();
                    Swal.fire({
                        title: data.success ? 'Success' : 'Error',
                        text: data.message,
                        icon: data.success ? 'success' : 'error',
                    });
                } catch (error) {
                    console.error('Fetch Error:', error);
                    Swal.fire('Error', 'An error occurred while resending the email.', 'error');
                }
            }
        });
    }
});


jQuery(function ($) {
	$('#profile_consent_field input').prop('checked', true);
	$('#profile_privacy_policy_text_field input').prop('checked', true);
	$('#check_box_1741676093107_field input').prop('checked', true);
	$('#check_box_1741676049_field input').prop('checked', true);
});


jQuery(document).ready(function ($) {

    function attachPhoneAutoFormat(selectors) {
        selectors.forEach(selector => {
            $(selector).each(function () {
                const $input = $(this);

                // Skip if already bound
                if ($input.data('phone-format-attached')) return;

                $input.data('phone-format-attached', true);

                $input.on('input', function (e) {
                    let value = e.target.value.replace(/\D/g, '').substring(0, 10); // Only digits, max 10

                    let formatted = '';
                    if (value.length > 0) formatted = '(' + value.substring(0, 3);
                    if (value.length >= 4) formatted += ') ' + value.substring(3, 6);
                    if (value.length >= 7) formatted += '-' + value.substring(6, 10);

                    e.target.value = formatted;
                });
            });
        });
    }

    // Run immediately
    const selectors = [
        '#addUserForm #phone',
        'form.register #profile_phone_number',
        '#affiliate-profile-form #billing_phone',
        '#user_registration_customer_phone_number',
    ];
    attachPhoneAutoFormat(selectors);

    function billingPhoneNumberValidation() {
        document.body.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'billing-phone') {
                let cursorPosition = e.target.selectionStart;
                let oldValue = e.target.value;

                // Strip non-digits and limit to 10
                let value = oldValue.replace(/\D/g, '').substring(0, 10);
                let formatted = '';

                if (value.length >= 1) {
                    formatted = '(' + value.substring(0, 3);
                }
                if (value.length >= 4) {
                    formatted += ') ' + value.substring(3, 6);
                }
                if (value.length >= 7) {
                    formatted += '-' + value.substring(6, 10);
                }

                e.target.value = formatted;

                let newCursorPosition = cursorPosition;
                if (formatted.length > oldValue.length && cursorPosition === oldValue.length) {
                    newCursorPosition = formatted.length;
                } else if (formatted.length < oldValue.length) {
                    newCursorPosition = Math.min(cursorPosition, formatted.length);
                }

                setTimeout(() => {
                    e.target.setSelectionRange(newCursorPosition, newCursorPosition);
                }, 0);
            }
        });

        document.body.addEventListener('blur', function(e) {
            if (e.target && e.target.id === 'billing-phone') {
                const value = e.target.value.replace(/\D/g, '');
                let isValid = /^[2-9][0-8][0-9][2-9][0-9]{6}$/.test(value);

                const errorClass = 'invalid-phone';
                const errorMsg = 'Please enter a valid US phone number';

                // Remove old error if exists
                const existingError = e.target.nextElementSibling;
                if (existingError && existingError.classList.contains('phone-error-message')) {
                    existingError.remove();
                }

                if (e.target.value !== '' && !isValid) {
                    e.target.classList.add(errorClass);

                    const errorEl = document.createElement('span');
                    errorEl.className = 'phone-error-message';
                    errorEl.style.cssText = 'color: #e74c3c; font-size: 12px; display: block; margin-top: 5px;';
                    errorEl.textContent = errorMsg;
                    e.target.insertAdjacentElement('afterend', errorEl);
                } else {
                    e.target.classList.remove(errorClass);
                }
            }
        }, true);

        document.body.addEventListener('paste', function(e) {
            if (e.target && e.target.id === 'billing-phone') {
                setTimeout(() => {
                    let value = e.target.value.replace(/\D/g, '').substring(0, 10);
                    let formatted = '';

                    if (value.length >= 1) {
                        formatted = '(' + value.substring(0, 3);
                    }
                    if (value.length >= 4) {
                        formatted += ') ' + value.substring(3, 6);
                    }
                    if (value.length >= 7) {
                        formatted += '-' + value.substring(6, 10);
                    }

                    e.target.value = formatted;
                }, 0);
            }
        });

        document.body.addEventListener('keydown', function(e) {
            if (e.target && e.target.id === 'billing-phone') {
                let cursorPosition = e.target.selectionStart;
                let value = e.target.value;

                if (e.key === 'Backspace') {
                    if (cursorPosition > 0) {
                        const charBefore = value.charAt(cursorPosition - 1);
                        if (['(', ')', ' ', '-'].includes(charBefore)) {
                            setTimeout(() => {
                                e.target.setSelectionRange(cursorPosition - 1, cursorPosition - 1);
                            }, 0);
                        }
                    }
                }

                if (e.key === 'Delete') {
                    const charAfter = value.charAt(cursorPosition);
                    if (['(', ')', ' ', '-'].includes(charAfter)) {
                        setTimeout(() => {
                            e.target.setSelectionRange(cursorPosition + 1, cursorPosition + 1);
                        }, 0);
                    }
                }
            }
        });
    }

    // Call this after the DOM is ready
    document.addEventListener('DOMContentLoaded', billingPhoneNumberValidation);


    // Re-run every 500ms to catch dynamically loaded inputs
    const intervalId = setInterval(() => {
        attachPhoneAutoFormat(selectors);
    }, 500);

    // Stop checking after 10 seconds
    setTimeout(() => clearInterval(intervalId), 10000);


    $("#customer-email-search").select2({
        placeholder: "Search by Customer Name or Email",
        allowClear: false,
        minimumInputLength: 3, // Wait until user types 3 characters
        language: {
        loadingMore: function () {
            return "Loading More Customer";
        }
        },
        ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        dataType: "json",
        delay: 250,
        data: function (params) {
            return {
            action: "search_customers_autosuggest",
            customer: params.term || '',
            page: params.page || 1
            };
        },
        processResults: function (data, params) {
            params.page = params.page || 1;
            return {
            results: data.results.map(function (item) {
                return { id: item.id, text: item.label };
            }),
            pagination: {
                more: data.pagination.more
            }
            };
        },
        cache: true
        }
    });

});