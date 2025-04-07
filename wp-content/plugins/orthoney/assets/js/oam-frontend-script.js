function process_group_popup(selectHtml = '') {
    if (selectHtml == '') {
      selectHtml = 'Please wait while we process your request.';
    }
    Swal.fire({
      title: 'Processing...',
      text: selectHtml,
    //   icon: 'info',
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  }
  
  function getURLParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
  }

  jQuery('select').each(function ($) {
    var placeholderText = jQuery(this).data('error-message') || 'Select an option';
    
    jQuery(this).select2({
        placeholder: placeholderText,
        allowClear: false
    });
});

jQuery(document).ready(function($) {
    
    if(getURLParam('action') == 'afficted-link' && getURLParam('token') != ''){

    }
    jQuery('#affiliate_select').select2({
        matcher: function(params, data) {
            if (jQuery.trim(params.term) === '') {
                return data;
            }

            // Ensure "OrtHoney" always appears at the top
            if (data.text.toLowerCase().includes(params.term.toLowerCase()) || data.id === 'OrtHoney') {
                return data;
            }

            return null;
        }
    });

});

function initTippy() {
    tippy('[data-tippy]', {
        content: (reference) => reference.getAttribute('data-tippy'),
        theme: 'translucent',
        animation: 'fade',
        arrow: true,
        allowHTML: true,
        followCursor: true,
    });
}

// Run on initial page load
document.addEventListener("DOMContentLoaded", initTippy);



(function () {
    document.querySelectorAll(".quantity").forEach((quantityContainer) => {
      const minusBtn = quantityContainer.querySelector(".minus");
      const plusBtn = quantityContainer.querySelector(".plus");
      const inputBox = quantityContainer.querySelector(".input-box");
  
      updateButtonStates();
  
      quantityContainer.addEventListener("click", handleButtonClick);
      inputBox.addEventListener("input", handleQuantityChange);
  
      function updateButtonStates() {
        const value = parseInt(inputBox.value);
        minusBtn.disabled = value <= 1;
        plusBtn.disabled = value >= parseInt(inputBox.max);
      }
  
      function handleButtonClick(event) {
        event.preventDefault();
        if (event.target.classList.contains("minus")) {
          decreaseValue();
        } else if (event.target.classList.contains("plus")) {
          increaseValue();
        }
      }
  
      function decreaseValue() {
        let value = parseInt(inputBox.value);
        value = isNaN(value) ? 1 : Math.max(value - 1, 1);
        inputBox.value = value;
        updateButtonStates();
        handleQuantityChange();
      }
  
      function increaseValue() {
        let value = parseInt(inputBox.value);
        value = isNaN(value) ? 1 : Math.min(value + 1, parseInt(inputBox.max));
        inputBox.value = value;
        updateButtonStates();
        handleQuantityChange();
      }
  
      function handleQuantityChange() {
        let value = parseInt(inputBox.value);
        value = isNaN(value) ? 1 : value;
        
        // Execute your code here based on the updated quantity value
        console.log("Quantity changed:", value);
      }
    });
  })();
  


  const greetingTextareas = document.querySelectorAll("#multiStepForm textarea, #recipient-manage-form form textarea");
  const maxChars = 250;
  
  if (greetingTextareas.length) {
      greetingTextareas.forEach((textarea) => {
          const textareaDiv = textarea.closest(".textarea-div"); // Find closest parent
          if (textareaDiv) {
              const charCounter = textareaDiv.querySelector(".char-counter span");
              if (charCounter) { // Ensure charCounter exists
                  textarea.addEventListener("input", () => {
                      let currentLength = textarea.value.length;
                      let remainingChars = maxChars - currentLength;
  
                      if (remainingChars < 0) {
                          textarea.value = textarea.value.substring(0, maxChars); // Prevent exceeding max limit
                          remainingChars = 0;
                      }
  
                      charCounter.textContent = `${remainingChars}`;
                  });
              }
          }
      });
  }
  

document.addEventListener('lity:open', function (event) {
    event.preventDefault();
    const popupOverlay = document.querySelector('.lity-wrap');
    if (popupOverlay) {
        popupOverlay.addEventListener('click', function (e) {
            if (e.target.classList.contains('lity-wrap')) {
                e.preventDefault(); // Prevents the default closing behavior
            }
        });
    }
});
document.addEventListener('lity:close', function(event) {
    event.preventDefault();
    // Get the closed modal's element
    const closedModal = event.target;
    
    // Check if the modal has an ID
    if (closedModal && closedModal.id) {
        const form = closedModal.querySelector('#recipient-manage-form form');
        if (form) {
            form.reset();
        }
    }
});


/*
Create new group Js Start
 */
const createGroupButtons = document.querySelectorAll('.createGroupButton');
if(createGroupButtons.length > 0){
    createGroupButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            // Prevent the default action
            event.preventDefault();

            const target = event.target;
            const formType = target.closest('form').getAttribute('data-formtype');
            let groupNameInput;
            let groupIdInput;
            let groupName;
            let groupId = '';
            let groupFormDiv = '';

            const msg = target.closest('div').querySelector('.response-msg');
            msg.textContent = '';

            if(formType == 'edit'){
                groupFormDiv = target.closest('.edit-group-form');
                groupNameInput = groupFormDiv.querySelector('.group_name');
                groupIdInput = groupFormDiv.querySelector('.group_id');
                groupName = groupNameInput.value;
                groupId = groupIdInput.value;
            }
            if(formType == 'create'){
                // Get the group name input field inside the closest form
                groupFormDiv = target.closest('.recipient-group-form');
                groupNameInput = groupFormDiv.querySelector('.group_name');
                
                // Get the value from the input field
                groupName = groupNameInput.value;

            
                // Check if the input value is empty
                if (groupName.trim() === '') {
                    msg.textContent = 'Please enter a group name.';
                    return;
                }
            }

            // Send AJAX request
            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'create_group',
                    group_name: groupName,
                    group_status: formType,
                    group_id: groupId,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Success:', data.data.message);
                    msg.textContent = data.data.message;
                    groupFormDiv.querySelector('form').reset();
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    console.error('Error:', data.data.message);
                    msg.textContent = 'Error: ' + data.data.message;
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                msg.textContent = 'An error occurred while creating the group.';
            });
        });
    });
}
/*
Create new group Js END
 */
/*
Deleted group Js Start
 */


document.addEventListener('click', function (event) {
    if (event.target.classList.contains('deleteGroupButton')) {
        console.log('sas');
        event.preventDefault();
        const target = event.target;
        const groupID = target.getAttribute('data-groupid');
        const groupName = target.getAttribute('data-groupname') || 'this recipient';

        Swal.fire({
            title: 'Are you sure?',
            html: 'You are removing <strong>' + groupName + '</strong> recipient list.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, I want',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                process_group_popup(); // Call the popup function before deleting

                fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'deleted_group',
                        group_id: groupID,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: data.message,
                            icon: 'success',
                            showConfirmButton: false,
                            timerProgressBar: false
                        });
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message,
                            icon: 'error',
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while deleting the group.',
                        icon: 'error',
                    });
                });
            }
        });
    }
});


/*
Deleted group Js End
 */


/*
Upload CSV End
 */
//
const groupsList = document.querySelectorAll('.groups-list');
if(groupsList.length > 0){
    groupsList.forEach(select => {
        select.addEventListener('change', function(event) {
            event.preventDefault();
            const target = event.target; // This is the element that triggered the event
            const groupListWrapper = target.closest('.recipient-group-section');
            const editFormButton = groupListWrapper.querySelector('.editGroupFormButton');
            
            if (editFormButton) {
                editFormButton.style.display = "block";
            }

            const editgroupform = groupListWrapper.querySelector('.edit-group-form');
            if (editgroupform) {
                editgroupform.style.display = "none";
            }

            if (groupListWrapper) {
                const editFormWrapper = groupListWrapper.querySelector('.edit-group-form-wrapper');
                if (editFormWrapper) {
                    editFormWrapper.style.display = "block";
                }
            }
        });
    });
}

/*
Edit group Js Start
 */

const editGroupFormButton = document.querySelectorAll('.editGroupFormButton');
if(editGroupFormButton.length > 0){
    editGroupFormButton.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const target = event.target; 
            target.style.display = 'none';
            
            const groupName = target.closest('.recipient-group-list').querySelector('select').selectedOptions[0].textContent;
            const groupId = target.closest('.recipient-group-list').querySelector('select').value;
            const editgroupformwrapper = target.closest('.edit-group-form-wrapper');
            if (editgroupformwrapper) {
                const editFormWrapper = editgroupformwrapper.querySelector('.edit-group-form');
                if (editFormWrapper) {
                    editFormWrapper.style.display = "block";
                    editFormWrapper.querySelector('.group_name').value = groupName;
                    editFormWrapper.querySelector('.group_id').value = groupId;
                }
            }
        });
        
    });
}
/*
Edit group Js END
 */

/*
Create group Js Start
 */

const createGroupFormButton = document.querySelectorAll('.createGroupFormButton');
if(createGroupFormButton.length > 0){
    createGroupFormButton.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const target = event.target; 
            target.style.display = 'none';
            const groupListWrapper = target.closest('.recipient-group-section');
            if (groupListWrapper) {
                const createFormWrapper = groupListWrapper.querySelector('.recipient-group-form');
                if (createFormWrapper) {
                    createFormWrapper.style.display = "block";
                }
            }
        });
    });
}

/*
Create group Js End
 */

const uploadRecipientButton = document.querySelectorAll('.uploadRecipientButton');
if(uploadRecipientButton.length > 0){
    uploadRecipientButton.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const target = event.target; 
            target.style.display = 'none';
            const groupListWrapper = target.closest('.recipient-group-section');
            if (groupListWrapper) {
                const createFormWrapper = groupListWrapper.querySelector('.upload-recipient-form');
                if (createFormWrapper) {
                    createFormWrapper.style.display = "block";
                }
            }
        });
    });
}

/*
Bulk Deleted Recipient in table Js Start
 */
document.addEventListener('click', function (event) {
    if (event.target.id === 'bulkMargeRecipient') {
        event.preventDefault();
        process_group_popup();

        const duplicateCSVData = document.querySelector('#duplicateCSVData');
        const groups = duplicateCSVData.querySelectorAll('.group-header');

        const ids = []; // Store all IDs
        groups.forEach(function (group) {
            const groupId = group.getAttribute('data-group');
            console.log(groupId);
            const dataGroupTrs = duplicateCSVData.querySelectorAll(
                'tr[data-group="' + groupId + '"]:not(.group-header)'
            );

            let selectedData = Array.from(dataGroupTrs).find(data => data.getAttribute('data-verify') == "1");

            // Fallback to data-verify="0" if no data-verify="1"
            if (!selectedData) {
                selectedData = Array.from(dataGroupTrs).find(data => data.getAttribute('data-verify') == "0");
            }

            const firstId = selectedData ? selectedData.getAttribute('data-id') : null;

            console.log(firstId);
            // Collect all other IDs except the first
            const remainingIds = Array.from(dataGroupTrs)
                .map(data => data.getAttribute('data-id'))
                .filter(id => id !== firstId);

            ids.push(...remainingIds);
        });
        console.log(ids);


        // AJAX request to pass the IDs to the 'bulkdelete' action
        if (ids.length > 0) {
            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bulk_deleted_recipient',
                    ids: JSON.stringify(ids) // Pass the array of IDs as a JSON string
                })
            })
            .then(response => response.json())
            .then(data => {
                // Handle the response from the server
                if (data.success) {
                       
                        Swal.fire({
                            title: data.data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });
                        
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);

                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.data.message,
                        icon: 'error',
                    });
                   
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Error:', error,
                    icon: 'error',
                });
            });
        }
    }
});

/*
Bulk Deleted Recipient in table Js End
 */


/*
Deleted Recipient in table Js Start
 */
document.addEventListener('click', function (event) {
    if (event.target.classList.contains('deleteRecipient')) {
        event.preventDefault();

        let method = 'process';
        const customer_dashboard_recipient_list = document.querySelector("#customer-dashboard-recipient-list");
        if(customer_dashboard_recipient_list){
            method = 'group';
            group_id = customer_dashboard_recipient_list.getAttribute('data-groupid');
            
        }

        let groupId = 0;
        let count = 0;
        let groupHeader= '';
        const target = event.target;

        const recipientTr = target.closest('tr');
        const recipientID = recipientTr?.getAttribute('data-id');
        const recipientname = target.getAttribute('data-recipientname');
        

        if (!recipientID) {
            Swal.fire({
                title: 'Error',
                text: 'Recipient ID not found.',
                icon: 'error',
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'You are removing ' + recipientname,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, remove the recipient!',
            cancelButtonText: 'Cancel',
            allowOutsideClick: true
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'deleted_recipient',
                        id: recipientID,
                        method: method
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        
                        Swal.fire({
                            title: 'Recipient removed successfully!',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });

                        setTimeout(function() {
                            window.location.reload();
                          }, 1500);
                        
                        // recipientTr.remove();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.data.message || 'Failed to remove recipient.',
                            icon: 'error',
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while removing the recipient.',
                        icon: 'error',
                    });
                });
            }
        });
    }
});
    
/*
Deleted group Js End
 */

/*
Edit and add Recipient form JS start
 */
const recipientManageForm = document.querySelector("#recipient-manage-form form");

function validateRecipientManageForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll("input[required], select[required], textarea[required]");

    requiredFields.forEach((field) => {
        let parentDiv = field.closest('.form-row'); // Ensure correct parent
        let errorMessage = parentDiv ? parentDiv.querySelector(".error-message") : null;

        if (!field.value.trim()) {
            field.style.border = "1px solid red";
            if (errorMessage) {
                errorMessage.textContent = field.getAttribute("data-error-message") || "This field is required.";
                errorMessage.style.color = "red";
                errorMessage.style.display = "block";
            }
            isValid = false;
        } else {
            field.style.border = "";
            if (errorMessage) {
                errorMessage.textContent = "";
                errorMessage.style.display = "none";
            }
        }
    });

    return isValid;
}

if (recipientManageForm) {
    recipientManageForm.addEventListener("submit", function (e) {
        e.preventDefault(); // Prevent form submission
        
        if (!validateRecipientManageForm(this)) {
            return; // Stop if validation fails
        }

        process_group_popup();

        let address_verified = 0;
        if (document.getElementById("unverifiedRecord") || document.getElementById("verifiedRecord")) {
            address_verified = 1;
        }

        let group_id = 0;
        let method = 'process';
        const customer_dashboard_recipient_list = document.querySelector("#customer-dashboard-recipient-list");
        if(customer_dashboard_recipient_list){
            method = 'group';
            group_id = customer_dashboard_recipient_list.getAttribute('data-groupid');
            
        }

        const formData = new FormData(this);
        formData.append('action', 'manage_recipient_form');
        formData.append('method', method);
        formData.append('group_id', group_id);
        formData.append('address_verified', address_verified);

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())  
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: data.data.message,
                    icon: 'success',
                    timer: 3500,
                    showConfirmButton: false,
                    timerProgressBar: true
                }).then(() => {
                    window.location.reload();
                    recipientManageForm.reset();
                });

                const currentLity = document.querySelector('[data-lity-close]');
                if (currentLity) {
                    currentLity.click();
                }
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.data.message || 'Failed to update recipient details.',
                    icon: 'error',
                });
            }
        })
        .catch(error => {
            console.error('Error during AJAX request:', error);
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while processing the request.',
                icon: 'error',
            });
        });
    });
}


/*
Edit button JS start
 */


document.addEventListener('click', function (event) {
    if (event.target.classList.contains('editRecipient') || event.target.classList.contains('viewRecipient')) {
        event.preventDefault();
        process_group_popup();
        
        const target = event.target;

        const recipientTr = target.closest('tr');
        let address_verified =  0;

        if(recipientTr){
            const recipientID = recipientTr.getAttribute('data-id');

            if (recipientTr.hasAttribute('data-address_verified')) {
                address_verified = recipientTr.getAttribute('data-address_verified');  
            } 

            let method = 'process';
            const customer_dashboard_recipient_list = document.querySelector("#customer-dashboard-recipient-list");
            if(customer_dashboard_recipient_list){
                method = 'group';
            }
            

            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_recipient_base_id',
                    id: recipientID,
                    address_verified : address_verified,
                    method : method
                }),
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if (data.success) {

                    const id         = data.data.id;
                    const full_name = data.data.full_name;
                    const company_name  = data.data.company_name;
                    const address_1  = data.data.address_1;
                    const address_2  = data.data.address_2;
                    const city       = data.data.city;
                    const state      = data.data.state;
                    const zipcode    = data.data.zipcode;
                    const quantity    = data.data.quantity;
                    const greeting    = data.data.greeting;
                    if (event.target.classList.contains('editRecipient')){
                    
                        const form = document.querySelector('#recipient-manage-form form');
                        form.querySelector('#recipient_id').value = id;
                        form.querySelector('#full_name').value    = full_name;
                        form.querySelector('#company_name').value = company_name;
                        form.querySelector('#address_1').value    = address_1;
                        form.querySelector('#address_2').value    = address_2;
                        form.querySelector('#city').value         = city;
                        form.querySelector('#state').value        = state;
                        form.querySelector('#zipcode').value      = zipcode;
                        form.querySelector('#quantity').value = quantity > 0 ? quantity : 1;
                        form.querySelector('#greeting').value     = greeting;
                    }
                    
                    if (event.target.classList.contains('viewRecipient')) {
                        let html = '<ul>';
                        html += "<li><label>Full Name:</label><span> " + (full_name ? full_name : '') + "</span></li>";
                        html += "<li><label>Company Name: </label><span>" + (company_name ? company_name : '') + "</span></li>";
                        html += "<li><label>Mailing Address: </label><span>" + (address_1 ? address_1 : '') + "</span></li>";
                        html += "<li><label>Suite/Apt#: </label><span>" + (address_2 ? address_2 : '') + "</span></li>";
                        html += "<li><label>City: </label><span>" + (city ? city : '') + "</span></li>";
                        html += "<li><label>State: </label><span>" + (state ? state : '') + "</span></li>";
                        html += "<li><label>Quantity: </label><span>" + (quantity ? quantity : '') + "</span></li>";
                        
                        html += "</ul>";
                        html += "<div class='recipient-view-greeting-box'><label>Greeting: </label><span>" + (greeting ? greeting : '') + "</span></div>";
                    
                        const viewpopup = document.querySelector('#recipient-view-details-popup .recipient-view-details-wrapper');
                        viewpopup.innerHTML = html;
                    }
                    setTimeout(function() {
                        lity(event.target.getAttribute('data-popup'));
                        
                    }, 250);
                    Swal.close();
                    
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.data.message || 'Failed to get recipient.',
                        icon: 'error',
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while removing the recipient.',
                    icon: 'error',
                });
            });
        }else{
            lity(event.target.getAttribute('data-popup'));
            Swal.close();
        }
    }

    if (event.target.classList.contains('removeRecipientsAlreadyOrder')) {
        let uniqueValues = new Set();
    
        ["failCSVData", "successCSVData", "duplicateCSVData", "newCSVData"].forEach((id) => {
            document.querySelectorAll(`#${id} tr`).forEach((row) => {
                let alreadyOrder = row.getAttribute("data-alreadyorder");
                if (alreadyOrder) {
                    alreadyOrder.split(",").forEach((value) => {
                        let trimmedValue = value.trim(); // Remove any spaces
                        if (trimmedValue) {
                            uniqueValues.add(trimmedValue); // Add to Set (auto handles uniqueness)
                        }
                    });
                }
            });
        });
    
        let uniqueArray = Array.from(uniqueValues); // Convert Set to Array
        console.log(uniqueArray);

        Swal.fire({
            title: 'Are you sure you want to remove '+ uniqueArray.length + ' recipients who have already received a jar this year?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, I want',
            cancelButtonText: 'No, I do not',
            allowOutsideClick: true,
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
               
                // fetch(oam_ajax.ajax_url, {
                //     method: 'POST',
                //     headers: {
                //         'Content-Type': 'application/x-www-form-urlencoded',
                //     },
                //     body: new URLSearchParams({
                //         action: 'affiliate_status_toggle_block',
                //         affiliate_id: affiliateCode,
                //         status: isBlocked
                //     }),
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         Swal.fire({
                //             title: 'Organization status changed successfully!',
                //             icon: 'success',
                //             showConfirmButton: false,
                //             timerProgressBar: false
                //         });

                //         setTimeout(function() {
                //             window.location.reload();
                //         }, 1500);
                //     } else {
                //         Swal.fire({
                //             title: 'Error',
                //             text: data.data.message || 'Failed to change status for organization.',
                //             icon: 'error',
                //         });
                //     }
                // })
                // .catch(() => {
                //     Swal.fire({
                //         title: 'Error',
                //         text: 'An error occurred while changing status for organization.',
                //         icon: 'error',
                //     });
                // });
            }
        });

        // TODO 
    }

    if (event.target.classList.contains('alreadyOrderButton')) {
        event.preventDefault();
        process_group_popup();
        const target = event.target;

        const recipientTr = target.closest('tr');

        if(recipientTr){
            const alreadyorder = recipientTr.getAttribute('data-alreadyorder');
            const recipientname = target.getAttribute('data-recipientname');
            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_alreadyorder_popup',
                    id: alreadyorder,
                    security: oam_ajax.nonce,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const viewAllAlreadyOrderPopup = document.querySelector('#viewAllAlreadyOrderPopup');
                    
                    viewAllAlreadyOrderPopup.querySelector('tbody').innerHTML = data.data.data;
                    viewAllAlreadyOrderPopup.querySelector('h3 span').innerHTML = recipientname;
                    setTimeout(function() {
                        lity('#viewAllAlreadyOrderPopup');
                    }, 250);
                    Swal.close();
                }
            }).catch(() => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while removing the recipient.',
                    icon: 'error',
                });
            });;
        }
    }
});


/*
Edit button JS END
*/
document.addEventListener('click', function (event) {
    if (event.target.id === 'download-failed-recipient-csv') {
        event.preventDefault();
        process_group_popup();
        const process_id = getURLParam('pid');
        let recipient_group_id = '';
        const customer_dashboard_recipient_list = document.querySelector("#customer-dashboard-recipient-list");
        if(customer_dashboard_recipient_list){

            recipient_group_id = customer_dashboard_recipient_list.getAttribute('data-groupid');
        }
        
        const params = new URLSearchParams({
            action: 'download_failed_recipient',
            type: process_id ? 'process' : 'group',
            id: process_id || recipient_group_id
        });

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const a = document.createElement('a');
                a.href = data.data.url;
                a.download = data.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => {
                    Swal.close();
                }, 500);
            } else {
                Swal.fire({
                    title: data.data.message,
                    icon: "error",
                    timer: 2000,
                    showConfirmButton: false,
                    timerProgressBar: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                  });
               
            }
        })
        .catch(error => console.error('AJAX Error:', error));
    }
});


document.addEventListener('click', function (event) {
    if (event.target.classList.contains('affiliate-block-btn')) {
        event.preventDefault();
        let isBlocked = event.target.getAttribute('data-blocked');
        let action = isBlocked ? 'unblock' : 'block';
        let affiliateCode = event.target.getAttribute('data-affiliate');

        Swal.fire({
            title: 'Are you sure you want to ' + action + ' this organization?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, I want',
            cancelButtonText: 'Cancel',
            allowOutsideClick: true,
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
               
                fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'affiliate_status_toggle_block',
                        affiliate_id: affiliateCode,
                        status: isBlocked
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Organization status changed successfully!',
                            icon: 'success',
                            showConfirmButton: false,
                            timerProgressBar: false
                        });

                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.data.message || 'Failed to change status for organization.',
                            icon: 'error',
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while changing status for organization.',
                        icon: 'error',
                    });
                });
            }
        });
    }
});

// affiliates manage
document.addEventListener("DOMContentLoaded", function () {
    let affiliateFilterButton = document.getElementById("affiliate-filter-button");
    let searchInput = document.getElementById("search-affiliates");
    let filterSelect = document.getElementById("filter-block-status");
    let affiliateResults = document.getElementById("affiliate-results");

    if(affiliateFilterButton){
        // Get the AJAX URL from localized script
        
        function fetchAffiliates() {
            process_group_popup();
            let searchValue = searchInput.value.trim();
            let filterValue = filterSelect.value;

            let requestData = new URLSearchParams();
            requestData.append("action", "search_affiliates");
            requestData.append("search", searchValue);
            requestData.append("filter", filterValue);

            fetch(oam_ajax.ajax_url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: requestData.toString(),
            })
            .then(response => response.text())
            .then(data => {
                affiliateResults.innerHTML = data;
                setTimeout(() => {
                    Swal.close();
                }, 500);
            })
            .catch(error => {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Error fetching affiliates: " + error.message,
                });
            });
        }


        // // Event listeners for search and filter
        affiliateFilterButton.addEventListener("click", function (event) {
            event.preventDefault();
            fetchAffiliates();
        });
    }
});

//incomplete order process code
document.addEventListener("DOMContentLoaded", function () {
    function fetchOrders(page = 1, $failed = 0) {
        process_group_popup();
        const params = new URLSearchParams();
        params.append("action", "orthoney_incomplete_order_process_ajax");
        params.append("page", page);
        params.append("failed", $failed);
        params.append("security", oam_ajax.nonce);

        fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById("incomplete-order-data");
            const paginationDiv = document.getElementById("incomplete-order-pagination");

            if (data.success) {
                const responseData = data.data;
                tableBody.innerHTML = responseData.table_content;
                paginationDiv.innerHTML = responseData.pagination;
                initTippy();
               
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.data?.message || "Something went wrong. Please try again",
                    icon: "error",
                });
            }
            setTimeout(() => {
                Swal.close();
            }, 500);
        })
        .catch(() => {
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while updating the Incomplete Order.',
                icon: 'error',
            });
        });
    }

    const Incomplete_orders = document.querySelector(".incomplete-order-block #incomplete-order-data");
    if (Incomplete_orders) {

        if (Incomplete_orders.hasAttribute('data-failed')) {
            fetchOrders(1, 1);
        }else{
            fetchOrders(1);
        }
        
        document.addEventListener('click', function (event) {
            if (event.target.matches('#incomplete-order-pagination a')) {
                event.preventDefault();
                const page = event.target.getAttribute('data-page');
                if (page) {
                    if (Incomplete_orders.hasAttribute('data-failed')) {
                        fetchOrders(page, 1);
                    }else{
                        fetchOrders(page);
                    }
                }
            }
        });
    }
});


//customer order process code
document.addEventListener("DOMContentLoaded", function () {
    function fetchOrders(page = 1, $failed = 0) {
        process_group_popup();
        const params = new URLSearchParams();
        params.append("action", "orthoney_customer_order_process_ajax");
        params.append("page", page);
        params.append("failed", $failed);
        params.append("security", oam_ajax.nonce);

        fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById("customer-order-data");
            const paginationDiv = document.getElementById("customer-order-pagination");

            if (data.success) {
                const responseData = data.data;
                tableBody.innerHTML = responseData.table_content;
                paginationDiv.innerHTML = responseData.pagination;
                initTippy();
               
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.data?.message || "Something went wrong. Please try again",
                    icon: "error",
                });
            }
            setTimeout(() => {
                Swal.close();
            }, 500);
        })
        .catch(() => {
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while updating the customer Order.',
                icon: 'error',
            });
        });
    }

    const Incomplete_orders = document.querySelector(".customer-order-block #customer-order-data");
    if (Incomplete_orders) {

        if (Incomplete_orders.hasAttribute('data-failed')) {
            fetchOrders(1, 1);
        }else{
            fetchOrders(1);
        }
        
        document.addEventListener('click', function (event) {
            if (event.target.matches('#customer-order-pagination a')) {
                event.preventDefault();
                const page = event.target.getAttribute('data-page');
                if (page) {
                    if (Incomplete_orders.hasAttribute('data-failed')) {
                        fetchOrders(page, 1);
                    }else{
                        fetchOrders(page);
                    }
                }
            }
        });
    }
});


//group order process code
document.addEventListener("DOMContentLoaded", function () {
    function fetchOrders(page = 1) {
        process_group_popup();
        const params = new URLSearchParams();
        params.append("action", "orthoney_groups_ajax");
        params.append("page", page);
        params.append("security", oam_ajax.nonce);

        fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById("groups-data");
            const paginationDiv = document.getElementById("groups-pagination");

            if (data.success) {
                const responseData = data.data;
                tableBody.innerHTML = responseData.table_content;
                paginationDiv.innerHTML = responseData.pagination;
                initTippy();
               
            } else {
                Swal.fire({
                    title: "Error",
                    text: data.data?.message || "Something went wrong. Please try again",
                    icon: "error",
                });
            }
            setTimeout(() => {
                Swal.close();
            }, 500);
        })
        .catch(() => {
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while updating the Incomplete Order.',
                icon: 'error',
            });
        });
    }

    const dashboard_groups = document.querySelector(".groups-block #groups-data");
    if (dashboard_groups) {
        fetchOrders(1);
                
        document.addEventListener('click', function (event) {
            if (event.target.matches('#groups-pagination a')) {
                event.preventDefault();
                const page = event.target.getAttribute('data-page');
                if (page) {
                    fetchOrders(page);
                }
            }
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

//Hide default user switching button
document.addEventListener('DOMContentLoaded', function () {
    const switchBackLink = document.querySelector('.woocommerce-MyAccount-navigation-link--user-switching-switch-back');
    if (switchBackLink) {
        switchBackLink.remove();
    }
});