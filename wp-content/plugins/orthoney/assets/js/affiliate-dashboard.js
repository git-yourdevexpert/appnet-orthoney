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
    const $input = $('#billing #billing-phone');

    // Bind input event
    $input.on('input', function (e) {
        let value = e.target.value.replace(/\D/g, '').substring(0, 10); // Remove non-digits, max 10

        let formatted = '';
        if (value.length > 0) formatted = '(' + value.substring(0, 3);
        if (value.length >= 4) formatted += ') ' + value.substring(3, 6);
        if (value.length >= 7) formatted += '-' + value.substring(6, 10);

        // Set formatted value in the input
        $(this).val(formatted);

        // Also explicitly update the "value" attribute
        $(this).attr('value', formatted);
    });
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
        '#user_registration_customer_phone_number',
        '#edit-billing-address-form #phone_number',
        '#billing_phone_field #billing_phone',
    ];
    attachPhoneAutoFormat(selectors);

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



document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("tracking-order-upload-form");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const fileInput = document.getElementById("fileInput");
    const file = fileInput?.files?.[0];

    if (!file) {
      Swal.fire({
        icon: "error",
        title: "Oops...",
        text: "Please select a file to upload!",
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: true
      });
      return;
    }

    if (!oam_ajax?.ajax_url || !oam_ajax?.nonce) {
      Swal.fire({
        icon: "error",
        title: "Security Error",
        text: "Security token missing. Please refresh the page and try again."
      });
      return;
    }

    // Show progress UI
    Swal.fire({
      title: "Uploading File",
      html: `
        <p>Please wait while your file is being uploaded...</p>
        <div style="width: 100%; background-color: #ccc; border-radius: 5px; overflow: hidden;">
          <div id="progress-bar" style="width: 0%; height: 10px;"></div>
        </div>
        <p id="progress-text">0%</p>
      `,
      didOpen: () => Swal.showLoading(),
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      showConfirmButton: false
    });

    const formData = new FormData();
    formData.append("action", "oam_tracking_order_manual_upload");
    formData.append("security", oam_ajax.nonce); // IMPORTANT: must match PHP check_ajax_referer()
    formData.append("csv_file", file);

    const xhr = new XMLHttpRequest();
    xhr.open("POST", oam_ajax.ajax_url, true);

    // Progress bar (no custom colors to keep it theme-agnostic)
    xhr.upload.addEventListener("progress", function (event) {
      if (!event.lengthComputable) return;
      const percent = Math.round((event.loaded / event.total) * 100);
      const bar = document.getElementById("progress-bar");
      const txt = document.getElementById("progress-text");
      if (bar) bar.style.width = percent + "%";
      if (txt) txt.textContent = percent + "%";
    });

    xhr.onload = function () {
      if (xhr.status !== 200) {
        Swal.fire({ icon: "error", title: "Error", text: "An error occurred during upload." });
        return;
      }

      let response;
      try { response = JSON.parse(xhr.responseText); }
      catch {
        Swal.fire({ icon: "error", title: "Invalid Response", text: "Server returned an invalid response." });
        return;
      }

      if (response.success) {
        Swal.fire({
          icon: "success",
          title: "Upload Complete!",
          text: response.data?.message || "Your file has been uploaded successfully.",
          showConfirmButton: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          timer: 1200
        }).then(() => window.location.reload());
      } else {
        Swal.fire({
          icon: "error",
          title: "Upload Failed",
          text: response.data?.message || "File upload failed. Please try again."
        });
      }
    };

    xhr.onerror = function () {
      Swal.fire({
        icon: "error",
        title: "Network Error",
        text: "A network error occurred during upload."
      });
    };

    xhr.send(formData);
  });
});


document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("tracking_order_import_button")) {
      e.preventDefault();

      const filename = e.target.getAttribute("data-filename"); // get data-filename from button
      const fileid = e.target.getAttribute("data-fileid"); // get data-fileid from button
      if (!filename) {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: "Filename not found!",
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          showConfirmButton: false,
        });
        return;
      }

      // Show processing popup first
      process_group_popup();

      let currentChunk = 0;
      let totalRows = 0;

      // Start processing after slight delay
      setTimeout(() => {
        function uploadChunk() {
          const formData = new FormData();
          formData.append("action", "tracking_order_number_insert");
          formData.append("security", oam_ajax.nonce);
          formData.append("current_chunk", currentChunk);
          formData.append("filename", filename);
          formData.append("fileid", fileid);

          const xhr = new XMLHttpRequest();
          xhr.open("POST", oam_ajax.ajax_url, true);

          xhr.onload = function () {
            if (xhr.status === 200) {
              try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                  if (currentChunk === 0) {
                    totalRows = response.data.total_rows;

                    // Show progress bar popup only after first success
                    Swal.fire({
                      title: "Processing Tracking Orders",
                      text: "Please wait, tracking order import is in progress.",
                      html: `
                        <p>Please wait, the tracking order import is in progress.</p>
                        <div style="width: 100%; background-color: #ccc; border-radius: 5px; overflow: hidden;">
                          <div id="progress-bar" style="width: 0%; height: 10px; background-color: #3085d6;"></div>
                        </div>
                        <p id="progress-text">0%</p>
                      `,
                      showConfirmButton: false,
                      allowOutsideClick: false,
                      allowEscapeKey: false,
                      allowEnterKey: false,
                    });
                  }

                  const progress = response.data.progress;
                  document.getElementById("progress-bar").style.width =
                    progress + "%";
                  document.getElementById("progress-text").innerText =
                    progress + "%";

                  if (!response.data.finished) {
                    currentChunk = response.data.next_chunk;
                    uploadChunk();
                  } else {
                    Swal.fire({
                      icon: "success",
                      title: "Import Complete!",
                      showConfirmButton: false,
                      allowOutsideClick: false,
                      allowEscapeKey: false,
                      allowEnterKey: false,
                    });
                    setTimeout(() => {
                      window.location.reload();
                    }, 1000);
                  }
                } else {
                  Swal.fire({
                    icon: "error",
                    title: "Import Failed",
                    text: response.data.message,
                  });
                }
              } catch (err) {
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: "Invalid server response.",
                });
              }
            } else {
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "An error occurred while processing the request.",
              });
            }
          };

          xhr.onerror = function () {
            Swal.fire({
              icon: "error",
              title: "Network Error",
              text: "A network error occurred during processing.",
            });
          };

          xhr.send(formData);
        }

        uploadChunk(); // start
      }, 500);
    }
  });
});



document.addEventListener("click", function (event) {
    if (event.target.classList.contains("recipientTrackingOrdersPopup")) {
      event.preventDefault();
    process_group_popup();

    const target = event.target;
    const popup = "#view-order-tracking-popup";

    // FIX: use correct dataset keys
    const recipientno = target.getAttribute("data-recipient_no");
    const recipientname = target.getAttribute("data-recipient_name") || "";

    if (!recipientno) {
      Swal.fire({
        title: "Error",
        text: "Recipient number is missing.",
        icon: "error",
      });
      return;
    }

    fetch(oam_ajax.ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "get_recipient_tracking_orders_popup",
        recipientno: recipientno,
        recipientname: recipientname,
        security: oam_ajax.nonce,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const recipientTrackingOrderPopup = document.querySelector(popup);
          recipientTrackingOrderPopup.querySelector("tbody").innerHTML = data.data.data;
          recipientTrackingOrderPopup.querySelector("h3 span").innerHTML = recipientname || recipientno;

          setTimeout(function () {
            lity(target.getAttribute("data-popup"));
            Swal.close();
          }, 500);

        } else {
          Swal.fire({
            title: "Error",
            text: "Could not fetch recipient tracking data.",
            icon: "error",
          });
        }
      })
      .catch(() => {
        Swal.fire({
          title: "Error",
          text: "An error occurred while fetching tracking data.",
          icon: "error",
        });
      });
  }
});
