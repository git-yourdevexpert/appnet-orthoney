function setCookie(name, value, days) {
    const domain = window.location.hostname;
    let cookie = `${name}=${value}; path=/; domain=${domain}; max-age=${days * 86400}`;
    
    // Check if the cookie exists, if so update it
    if (document.cookie.split('; ').find(row => row.startsWith(name))) {
        // Cookie exists, update it
        document.cookie = cookie;
    } else {
        // Cookie does not exist, set it
        document.cookie = cookie;
    }
}

function VerifyRecipientsDatatable(){
    ["failCSVData", "successCSVData", "newCSVData"].forEach((id) => {
      const div = document.getElementById(id);
      if (div && div.innerHTML.trim() !== "") {
        const tableEl = div.querySelector("table");
        if (tableEl && !jQuery(tableEl).hasClass('dataTable')) {
          const $table = jQuery(tableEl);
    
          // Initialize DataTable
          const dataTable = $table.DataTable({
            paging: false,
            info: true,
            searching: true,
            responsive: true,
            deferRender: false,
            lengthChange: false
          });
    
          // Hide pagination & info if only 1 page
          dataTable.on('draw', function () {
            const pageInfo = dataTable.page.info();
            const wrapper = $table.closest('.dataTables_wrapper');
            const pagination = wrapper.find('.dataTables_paginate');
            const infoText = wrapper.find('.dataTables_info');
    
            if (pageInfo.pages <= 1) {
              pagination.hide();
              infoText.hide();
            } else {
              pagination.show();
              infoText.show();
            }
          });
    
          // Trigger initial check
          dataTable.draw();
        }
      }
    });
  }

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
            if (data.text.toLowerCase().includes(params.term.toLowerCase()) || data.id === 'Orthoney') {
                return data;
            }

            return null;
        }
    });

});

function initTippy() {
    document.querySelectorAll('[data-tippy]').forEach((el) => {
        if (el._tippy) {
            el._tippy.destroy(); // Destroy existing tooltip instance
        }
        tippy(el, {
            content: el.getAttribute('data-tippy'),
            theme: 'translucent',
            animation: 'fade',
            arrow: true,
            allowHTML: true,
            followCursor: true,
            trigger: 'mouseenter',
        });
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
  
  const greetingTextareas = document.querySelectorAll("#multiStepForm textarea, #recipient-manage-form form textarea, #recipient-order-manage-popup textarea");
  const maxChars = 250;
  
  if (greetingTextareas.length) {
      greetingTextareas.forEach((textarea) => {
          const textareaDiv = textarea.closest(".textarea-div"); // Find closest parent
          if (textareaDiv) {
              const charCounter = textareaDiv.querySelector(".char-counter span");
              if (charCounter) { // Ensure charCounter exists
                  const updateCharCount = () => {
                      let currentLength = textarea.value.length;
                      let remainingChars = maxChars - currentLength;
  
                      if (remainingChars < 0) {
                          textarea.value = textarea.value.substring(0, maxChars); // Trim excess
                          remainingChars = 0;
                      }
  
                      charCounter.textContent = `${remainingChars}`;
                  };
  
                  // Initialize on page load
                  updateCharCount();
  
                  // Update on input
                  textarea.addEventListener("input", updateCharCount);
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
    if (event.target.classList.contains('viewAllRecipientsPopupCheckout')) {
        if (event.target.classList.contains('viewAllRecipientsPopupCheckout')) {
            setTimeout(function () {
                const $dataTables_wrapper = jQuery('#viewAllRecipientsPopupCheckout .dataTables_wrapper');
                if($dataTables_wrapper.length == 0){

                
                const $table = jQuery('#viewAllRecipientsPopupCheckout table');
                    $table.DataTable({
                        paging: true,
                        info: true,
                        searching: true,
                        responsive: true,
                        deferRender: false,
                        lengthChange: false,
                        columnDefs: [
                            { targets: '_all', }
                        ]
                    });
                }
            }, 200);
        }
    }
    
    if (event.target.classList.contains('wcReOrderCustomerDashboard')) {
        event.preventDefault();
        const target = event.target;
        const userID = target.getAttribute('data-user');
        const orderid = target.getAttribute('data-orderid');

        // process_group_popup();

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wc_re_order_customer_dashboard',
                userID: userID,
                orderid: orderid,
                security: oam_ajax.nonce
            }),
        })
        .then(response => response.json())
        .then(data => {
            process_group_popup();
            if (data.success) {
                Swal.fire({
                    title: data.data.message,
                    icon: 'success',
                    showConfirmButton: false,
                    timerProgressBar: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                });
                setTimeout(function() {
                    window.location.href = data.data.redirect_url;
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
        
        
        Swal.close();

        // AJAX request to pass the IDs to the 'bulkdelete' action
        if (ids.length > 0) {

            Swal.fire({
                title: 'Are you sure?',
                text: 'Keep 1 Entry and Delete Other Duplicate Entries',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, I want!',
                cancelButtonText: 'No, I do not',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
            }).then((result) => {
                if (result.isConfirmed) {
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
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
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
        const step = document.querySelector(".step-nav-item.active").getAttribute('data-step');
        if(step == 4){
        // if (document.getElementById("unverifiedRecord") || document.getElementById("verifiedRecord")) {
            address_verified = 1;
        }

        let group_id = 0;
        let method = 'process';
        const customer_dashboard_recipient_list = document.querySelector("#customer-dashboard-recipient-list");
        if(customer_dashboard_recipient_list){
            method = 'group';
            group_id = customer_dashboard_recipient_list.getAttribute('data-groupid');
        }

        const recipient_order_data = document.querySelector("#recipient-order-data");

        if(recipient_order_data){
            method = 'order';
            group_id = 0;
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
        const form = document.querySelector('#recipient-manage-form form');
        form.reset();
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
                        form.querySelector('button[type="submit"]').innerHTML = 'Edit Recipient Details';
                        form.querySelector('.textarea-div .char-counter span').innerHTML = 250 - form.querySelector('#greeting').value.length;
                    }
                    
                    if (event.target.classList.contains('viewRecipient')) {
                        let html = '<ul>';
                        html += "<li><label>Full Name</label><span> " + (full_name ? full_name : '') + "</span></li>";
                        html += "<li><label>Company Name </label><span>" + (company_name ? company_name : '') + "</span></li>";
                        html += "<li><label>Mailing Address </label><span>" + (address_1 ? address_1 : '') + "</span></li>";
                        html += "<li><label>Suite/Apt# </label><span>" + (address_2 ? address_2 : '') + "</span></li>";
                        html += "<li><label>City </label><span>" + (city ? city : '') + "</span></li>";
                        html += "<li><label>State </label><span>" + (state ? state : '') + "</span></li>";
                        html += "<li><label>Quantity </label><span>" + (quantity ? quantity : '') + "</span></li>";
                        
                        html += "</ul>";
                        html += "<div class='recipient-view-greeting-box'><label>Greeting </label><span>" + (greeting ? greeting : '') + "</span></div>";
                    
                        const viewpopup = document.querySelector('#recipient-view-details-popup .recipient-view-details-wrapper');
                        viewpopup.innerHTML = html;
                        form.querySelector('[type="submit"]').innerHTML = 'Add New Recipient Details';
                    }
                    setTimeout(function() {
                        lity(event.target.getAttribute('data-popup'));
                        if (event.target.classList.contains('editRecipient')){
                            form.querySelector('button[type="submit"]').innerHTML = 'Edit Recipient Details';
                        }else{
                            form.querySelector('[type="submit"]').innerHTML = 'Add New Recipient Details';
                        }
                        jQuery(form).find('#state').val(state).trigger('change');
                        
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
            form.reset();
            lity(event.target.getAttribute('data-popup'));
            Swal.close();
        }
    }

    if (event.target.classList.contains('viewSuccessRecipientsAlreadyOrder')) {
        event.preventDefault();
    
        const status = event.target.getAttribute('data-status');
    
        if (status == '0') {
            event.target.setAttribute('data-status', '1');
            event.target.textContent = "View All Recipients";
            event.target.setAttribute('data-tippy', 'Back to view all the verified recipients.');
    
            ["successCSVData"].forEach((id) => {
                document.querySelectorAll(`#${id} tbody tr`).forEach((row) => {
                    let alreadyOrder = row.getAttribute("data-alreadyorder");
                    if (alreadyOrder) {
                        row.classList.remove("hide");
                    } else {
                        row.classList.add("hide");
                    }
                });
    
                const el = document.querySelector(`#${id} .view-all-recipients`);
                if (el) {
                    el.style.display = "none";
                }
            });

            VerifyRecipientsDatatable();
    
        } else {
            event.target.setAttribute('data-status', '0');
            event.target.textContent = "View Already Ordered Recipients";
            event.target.setAttribute('data-tippy', 'View all recipients who have already placed an order this season.');
            ["successCSVData"].forEach((id) => {
                const rows = document.querySelectorAll(`#${id} tr`);
                rows.forEach((row, index) => {
                    // hide all rows
                    row.classList.add("hide");
                });
    
                // Show only first 10 rows
                for (let i = 0; i < 10; i++) {
                    if (rows[i]) {
                        rows[i].classList.remove("hide");
                    }
                }
    
                const el = document.querySelector(`#${id} .view-all-recipients`);
                if (el) {
                    el.style.display = "block";
                }
            });
        }
        setTimeout(() => {
            initTippy();
        }, 250);
    }
    

    if (event.target.classList.contains('removeRecipientsAlreadyOrder')) {
        event.preventDefault();
     
        let count = 0;
        let idList = [];
        // ["failCSVData", "successCSVData", "duplicateCSVData", "newCSVData"].forEach((id) => {
        
        ["successCSVData"].forEach((id) => {
            document.querySelectorAll(`#${id} tr`).forEach((row) => {
                let alreadyOrder = row.getAttribute("data-alreadyorder");
                if (alreadyOrder) {
                    count += 1;
        
                    let dataId = row.getAttribute("data-id");
                    if (dataId) {
                        idList.push(dataId);
                    }
        
                }
            });
        });
        

        Swal.fire({
            title: 'Are you sure you want to remove '+ count + ' recipients who have already received a jar this year from the list?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, remove them',
            cancelButtonText: 'No, keep them',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        }).then((result) => {
            if (result.isConfirmed) {
               
                fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'remove_recipients_already_order_this_year',
                        ids: idList,
                        security: oam_ajax.nonce,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: data.data.message,
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
                            text: data.data.message || 'Failed to recipients removed.',
                            icon: 'error',
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while recipients removed.',
                        icon: 'error',
                    });
                });
            }
        });

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

document.addEventListener("DOMContentLoaded", function () {

    count = 0;
    ["successCSVData"].forEach((id) => {
        document.querySelectorAll(`#${id} tr`).forEach((row) => {
            let alreadyOrder = row.getAttribute("data-alreadyorder");
            if (alreadyOrder) {
                count += 1;
            }
        });
    });

    const viewSuccessRecipientsAlreadyOrder = document.querySelector(".viewSuccessRecipientsAlreadyOrder");
    const removeRecipientsAlreadyOrder = document.querySelector(".removeRecipientsAlreadyOrder");
    if(count != 0){
        if(viewSuccessRecipientsAlreadyOrder){
            viewSuccessRecipientsAlreadyOrder.style.display = 'inline-block';
        }
        if(removeRecipientsAlreadyOrder){
            removeRecipientsAlreadyOrder.style.display = 'inline-block';
        }
    }else{
        if(viewSuccessRecipientsAlreadyOrder){
            viewSuccessRecipientsAlreadyOrder.style.display = 'none';
        }
        if(removeRecipientsAlreadyOrder){
            removeRecipientsAlreadyOrder.style.display = 'none';
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
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
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


//Group Listing code
jQuery(document).ready(function ($) {
    const $table = $('#group-recipient-table');

    const dataTable = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: oam_ajax.ajax_url,
            type: 'POST',
            data: function (d) {
                d.action = 'orthoney_group_recipient_list_ajax';
                d.security = oam_ajax.nonce;
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'recipient_count' },
            { data: 'date' },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            if (typeof initTippy === "function") {
                initTippy();
            }
        },
        language: {
            emptyTable: "No recipient group found"
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Export CSV',
                filename: 'group_recipient_list_export',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            }
        ]
    });
});

//incomplete order process code
jQuery(document).ready(function ($) {
    const $table = $('#incomplete-order-table');
    const failedFlag = $table.data('failed') ? 1 : 0;

    const emptyMessage = failedFlag === 1
        ? "No Failed Recipients list found"
        : "No incomplete orders found";

    const dataTable = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: oam_ajax.ajax_url,
            type: 'POST',
            data: function (d) {
                d.action = 'orthoney_incomplete_order_process_ajax';
                d.security = oam_ajax.nonce;
                d.failed = failedFlag;
            }
        },
        columns: [
            { data: 'id' },
            { data: 'name' },
            { data: 'ordered_by' },
            { data: 'date' },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            if (typeof initTippy === "function") {
                initTippy();
            }
        },
        language: {
            emptyTable: emptyMessage
        },
        dom: 'Bfrtip', // ✅ Important for buttons to show up
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Export CSV',
                filename: 'incomplete_orders_export',
                exportOptions: {
                    columns: ':not(:last-child)' // Exclude the last column (action)
                }
            }
        ]
    });
});

/**
 * Recipient Order Start
 */

document.addEventListener('DOMContentLoaded', function () {
    const recipientOrderData = document.querySelector('#recipient-order-data');

    if (recipientOrderData) {
        setTimeout(() => {
            jQuery('#recipient-order-data table').DataTable({
                paging: true,
                info: true,
                searching: true,
                responsive: true,
                deferRender: false,
                lengthChange: true,
                columnDefs: [
                    {
                        targets: -1,
                        orderable: false
                    }
                ]
            });
        }, 200);
    }
});

document.addEventListener('click', function (event) {
    const target = event.target;
    const isEdit = target.classList.contains('editRecipientOrder');
    const isView = target.classList.contains('viewRecipientOrder');

    if (!isEdit && !isView) return;

    event.preventDefault();
    process_group_popup();

    const form = document.querySelector('#recipient-manage-order-form form');
    form.reset();

    const orderID = target.getAttribute('data-order');

    fetch(oam_ajax.ajax_url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_recipient_order_base_id',
            id: orderID,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (!data.success) {
                throw new Error(data.data.message || 'Failed to get recipient.');
            }

            const d = data.data;

            if (isEdit) {
                const popuptitle = document.querySelector('#recipient-order-manage-popup .popup-title span');
                popuptitle.innerHTML = '#' + orderID;
                const fields = ['order_id', 'full_name', 'company_name', 'address_1', 'address_2', 'city', 'state', 'zipcode','quantity' ,'greeting'];
                fields.forEach(field => {
                    const input = form.querySelector(`#${field}`);
                    if (input) input.value = d[field] || '';
                });
                form.querySelector('button[type="submit"]').innerHTML = 'Edit Recipient Order Details';
                document.querySelector('.textarea-div .char-counter span').innerHTML = 250 - d.greeting.length;
                
            }

            if (isView) {
                const viewpopup = document.querySelector('#recipient-order-edit-popup .recipient-view-details-wrapper');
                const popuptitle = document.querySelector('#recipient-order-edit-popup .popup-title span');
                popuptitle.innerHTML = '#' + orderID;
                viewpopup.innerHTML = `
                    <ul>
                        <li><label>Full Name</label><span>${d.full_name || ''}</span></li>
                        <li><label>Company Name</label><span>${d.company_name || ''}</span></li>
                        <li><label>Mailing Address</label><span>${d.address_1 || ''}</span></li>
                        <li><label>Suite/Apt#</label><span>${d.address_2 || ''}</span></li>
                        <li><label>City</label><span>${d.city || ''}</span></li>
                        <li><label>State</label><span>${d.full_state || ''}</span></li>
                        <li><label>Zipcode</label><span>${d.zipcode || 0}</span></li>
                        <li><label>Quantity</label><span>${d.quantity || 0}</span></li>
                    </ul>
                    <div class='recipient-view-greeting-box'>
                        <label>Greeting</label><span>${d.greeting || ''}</span>
                    </div>`;
            }

            setTimeout(() => {
                lity(target.getAttribute('data-popup'));

                const submitButton = form.querySelector('button[type="submit"]');
                if (isEdit) {
                    submitButton.style.display = 'block';
                    submitButton.innerHTML = 'Edit Recipient Order Details';
                } else {
                    submitButton.style.display = 'none';
                }

                jQuery(form).find('#state').val(d.state).trigger('change');
            }, 250);

            Swal.close();
        })
        .catch((error) => {
            Swal.fire({
                title: 'Error',
                text: error.message || 'An error occurred while retrieving the recipient.',
                icon: 'error',
            });
        });
});
const recipientOrderManageForm = document.querySelector("#recipient-manage-order-form form");

function validateRecipientOrderManageForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll("input[required], select[required], textarea[required]");

    requiredFields.forEach((field) => {
        const parentDiv = field.closest('.form-row');
        const errorMessage = parentDiv?.querySelector(".error-message");

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

if (recipientOrderManageForm) {
    recipientOrderManageForm.addEventListener("submit", function (e) {
        e.preventDefault();

        if (!validateRecipientOrderManageForm(this)) return;

        process_group_popup();

        const formData = new FormData(this);
        formData.append('action', 'manage_recipient_order_form');

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    throw new Error(data.data.message || 'Failed to update recipient details.');
                }

                Swal.fire({
                    title: data.data.message,
                    icon: 'success',
                    timer: 3500,
                    showConfirmButton: false,
                    timerProgressBar: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    allowEnterKey: false,
                });
                
                window.location.reload();

                document.querySelector('[data-lity-close]')?.click();
            })
            .catch((error) => {
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'An error occurred while processing the request.',
                    icon: 'error',
                });
            });
    });
}


jQuery(document).on('click', 'input[name="table_order_type"]', function (e) {
    e.preventDefault();
    var otype = jQuery(this).val();
    if(otype == "main_order"){
        
        jQuery("#customer-orders-table_wrapper").show();
        jQuery("#customer-jar-orders-table_wrapper").hide();
        jQuery("#customer-jar-orders-table").hide();

        // customer-orders-table_filter
        // customer-orders-table_info
    }else if(otype == "sub_order_order"){
        
        
        jQuery("#customer-orders-table_wrapper").hide();
        jQuery("#customer-jar-orders-table_wrapper").show();
        jQuery("#customer-jar-orders-table").show();

       
    }

});


/**
 * Recipient Jar order start 
 */
 
jQuery(document).ready(function ($) {
    var table = $('#customer-jar-orders-table').DataTable({
        processing: false,
        serverSide: true,
        select: {
            style: 'multi' // or 'single'
          },
        ajax: {
            url: oam_ajax.ajax_url,
            type: 'POST',
            data: function (d) {
                d.action = 'orthoney_customer_order_process_ajax';
                d.security = oam_ajax.nonce;
                d.custom_order_type = $('#custom-order-type-filter').val();
                d.custom_order_status = $('#custom-order-status-filter').val();
                d.table_order_type = 'sub_order_order';
            },
            beforeSend: function () {
                process_group_popup('Please wait while we process your request.');
            },
            complete: function () {
                setTimeout(() => {
                    
                    Swal.close();
                }, 1300);
            },
            error: function () {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while loading your orders.',
                    icon: 'error',
                });
            },
        },
        columns: [
            // {
            //     title: '<input type="checkbox" class="selectall-checkbox">',
            //     data: null,
            //     orderable: false,
            //     searchable: false,
            //     className: 'checkbox-col',
            //     render: function (data, type, row) {
            //         return `<input type="checkbox" class="row-checkbox" value="${row.order_no}" data-order-id="${row.order_no}">`;
            //     }
            // },
            { data: 'jar_no' },
            { data: 'date' },
            { data: 'billing_name', orderable: false, searchable: false },
            { data: 'affiliate_code', orderable: false, searchable: false },
            { data: 'total_jar', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false },
           // { data: 'price', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            initTippy();
            $('#customer-jar-orders-table tbody tr.sub-order-row').remove();
            $('#customer-jar-orders-table tbody tr').each(function () {
                const $subOrderRow = $('<div class="sub-order-list"></div>');
                $(this).after($subOrderRow);
            });
        },
        initComplete: function () {
            jQuery("#customer-jar-orders-table_wrapper").hide();
            jQuery("#customer-jar-orders-table_length").hide();

            
            // const customFilter = `
            //     <label style="margin-left: 10px;">
            //         Ship Type:
            //         <select id="custom-order-type-filter" class="custom-select form-control">
            //             <option value="all">All Ship Types</option>
            //             <option value="single_address">Single Address</option>
            //             <option value="multiple_address">Multiple Addresses</option>
            //         </select>
            //     </label>
            //        <label style="margin-left: 10px;">
            //         PDF Export Type:
            //         <select id="custom-pdf-export-type" class="custom-pdf-export-type form-control">
            //             <option value="all">PDF Types</option>
            //             <option value="2p">2P: Online & paper orders (print version)</option>
            //             <option value="2e">2E: Online & paper orders (email version)</option>
            //             <option value="4p">4P: Online & paper orders (print version)</option>
            //             <option value="4e">4E: Online & paper orders (email version)</option>
            //             <option value="5p">5p</option>
            //         </select>
            //     </label>
            //     <label style="margin-left: 10px;" class="custom-order-status-filter-wrapper">
            //         Order Status:
            //         <select id="custom-order-status-filter" class="custom-select form-control">
            //             <option value="all">All Status</option>
            //             <option value="wc-pending">Pending payment</option>
            //             <option value="wc-processing">Processing</option>
            //             <option value="wc-on-hold">On hold</option>
            //             <option value="wc-completed">Completed</option>
            //             <option value="wc-cancelled">Cancelled</option>
            //             <option value="wc-refunded">Refunded</option>
            //             <option value="wc-failed">Failed</option>
            //             <option value="wc-checkout-draft">Draft</option>
            //         </select>
            //     </label>
            // `;
            // $('#customer-orders-table_filter').append(customFilter);
            // $('#customer-orders-table_filter').append('<label style="margin-left: 10px;">&nbsp;<div><button class="order-export-data w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Data</button></div></label>');
          //  $('#customer-orders-table_filter').append('<label style="margin-left: 10px;">&nbsp;<div><button class="order-pdf-export w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Pdf</button></div></label>');
 
            const tableType = `
                <label for="main_order">
                    <input type="radio" id="main_order" name="table_order_type" value="main_order" >
                    <span>Main Order</span>
                </label>
                <label for="sub_order_order" style="margin-left: 15px;">
                    <input type="radio" id="sub_order_order" name="table_order_type" value="sub_order_order" checked>
                    <span>Jar Order</span>
                </label>
            `;
            $('#customer-jar-orders-table_length').before('<div style="text-align:center; margin-bottom: 10px;">' + tableType + '</div>');
 
           // toggleRecipientColumn();
 
            // $('#custom-order-type-filter, #custom-order-status-filter').on('change', function (e) {
            //     e.preventDefault();
            //     table.ajax.reload();
            // });
 
            $(document).on('click', 'input[name="table_order_type"]', function (e) {
                e.preventDefault();
                //toggleRecipientColumn();
              //  table.ajax.reload();
            });
 
 
            jQuery('.selectall-checkbox').on('change', function() {
                jQuery('.row-checkbox').prop('checked', this.checked);
              });
 
 
            // 🔄 Export data trigger (new AJAX call)
            // $(document).on('click', '.order-export-data', function (e) {
            //     e.preventDefault();
 
            //     const requestData = {
            //         action: 'orthoney_customer_order_export_ajax',
            //         security: oam_ajax.nonce,
            //         custom_order_type: $('#custom-order-type-filter').val(),
            //         custom_order_status: $('#custom-order-status-filter').val(),
            //         table_order_type: $('input[name="table_order_type"]:checked').val(),
            //         search: {
            //             value: $('#customer-jar-orders-table_filter input').val()
            //         }
            //     };
 
            //     process_group_popup('Generating CSV...');
 
            //     $.post(oam_ajax.ajax_url, requestData, function (response) {
            //         Swal.close();
            //         if (response.success && response.data?.url) {
            //             const a = document.createElement('a');
            //             a.href = response.data.url;
            //             a.download = response.data.filename;
            //             document.body.appendChild(a);
            //             a.click();
            //             document.body.removeChild(a);
            //         } else {
            //             Swal.fire({
            //                 title: 'Export Failed',
            //                 text: response?.data?.message || 'Something went wrong during export.',
            //                 icon: 'error',
            //             });
            //         }
            //     });
            // });
 
 
            // $(document).on('click', '.order-pdf-export', function (e) {
            //     e.preventDefault();
 
 
            //     let selectedValues = [];
 
            //     $('.row-checkbox:checked').each(function () {
            //         selectedValues.push($(this).val());
            //     });
 
            //     if (selectedValues.length === 0) {
            //         Swal.fire({
            //             title: 'No Order Selected',
            //             text: 'Please check at least one order before proceeding.',
            //             icon: 'warning',
            //         });
            //         return; // Stop further execution
            //     }
 
            //   //  console.log(selectedValues);
 
            //     const requestData = {
            //         action: 'orthoney_customer_order_export_pdf_ajax',
            //         security: oam_ajax.nonce,
            //         custom_order_type: $('#custom-order-type-filter').val(),
            //         custom_order_status: $('#custom-order-status-filter').val(),
            //         custom_order_pdf_type: $('#custom-pdf-export-type').val(),
            //         table_order_type: $('input[name="table_order_type"]:checked').val(),
            //         selectedValues:selectedValues,
            //         search: {
            //             value: $('#customer-orders-table_filter input').val()
            //         }
            //     };
 
            //     process_group_popup('Generating PDF...');
 
            //     $.post(oam_ajax.ajax_url, requestData, function (response) {
            //         Swal.close();
            //         if (response.success && response.data?.url && response.data.request == "download") {
            //             const a = document.createElement('a');
            //             a.href = response.data.url;
            //             a.download = response.data.filename;
            //             document.body.appendChild(a);
            //             a.click();
            //             document.body.removeChild(a);
 
            //             setTimeout(() => {
            //                 $.post(oam_ajax.ajax_url, {
            //                     action: 'remove_pdf_data',
            //                     file_url: response.data.url
            //                 });
            //             }, 20000); // 5000ms = 5 seconds
               
 
            //         } else if(response.data?.request) {
            //             Swal.fire({
            //                 title: 'PDF file has been sent on email',
            //                 text: response?.data?.message || 'Something went wrong during export.',
            //                 icon: 'success',
            //             });
            //             setTimeout(() => {
            //                 $.post(oam_ajax.ajax_url, {
            //                     action: 'remove_pdf_data',
            //                     file_url: response.data.url
            //                 });
            //             }, 20000); // 5000ms = 5 seconds
                       
            //         } else {
            //             Swal.fire({
            //                 title: 'Export Failed',
            //                 text: response?.data?.message || 'Something went wrong during export.',
            //                 icon: 'error',
            //             });
            //         }
            //     });
            // });
 
 
            // setTimeout(function () {
            //     const searchBox = $('#customer-orders-table_filter input');
            //     searchBox.attr('placeholder', 'Search by name');
           
            //     searchBox.off('input').on('input', function () {
            //         const val = this.value.trim();
            //         const wordCount = val.split(/\s+/).filter(Boolean).length;
           
            //         if (wordCount >= 3 || val.length === 0) {
            //             table.search(val).draw();
            //         }
            //     });
            // }, 100);
 
          
        }
    });
});


/**
 * Recipient Order End
 */
 
jQuery(document).ready(function ($) {

    const recipientOrderID = getURLParam('recipient-order');
    if (recipientOrderID) {
        const recipientOrderElement = $('.viewRecipientOrder[data-order="' + recipientOrderID + '"]');
        if (recipientOrderElement.length) {
            const loader = document.querySelector(".multiStepForm.loader");
            loader.style.display = "none";
            recipientOrderElement.trigger('click');
        }
    }

    var table = $('#customer-orders-table').DataTable({
        processing: false,
        serverSide: true,
        select: {
            style: 'multi' // or 'single'
          },
        ajax: {
            url: oam_ajax.ajax_url,
            type: 'POST',
            data: function (d) {
                d.action = 'orthoney_customer_order_process_ajax';
                d.security = oam_ajax.nonce;
                d.custom_order_type = $('#custom-order-type-filter').val();
                d.custom_order_status = $('#custom-order-status-filter').val();
                d.table_order_type = $('input[name="table_order_type"]:checked').val();
                d.selected_customer_id = $('#select-customer').val(); // 
                d.selected_order_status = $('#order_status').val(); // 
                d.selected_year = $('#select-year').val(); // 
                const qtySlider = $('#slider-range').slider("values");
                d.selected_min_qty = qtySlider[0] || 1;
                d.selected_max_qty = qtySlider[1] || 1000;

            },
            beforeSend: function () {
                process_group_popup('Please wait while we process your request.');
            },
            complete: function () {
                setTimeout(() => {
                    Swal.close();
                }, 1300);
            },
            error: function () {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while loading your orders.',
                    icon: 'error',
                });
            },
        },
        columns: [
            {
                title: '<input type="checkbox" class="selectall-checkbox">',
                data: null,
                orderable: false,
                searchable: false,
                className: 'checkbox-col',
                render: function (data, type, row) {
                    return `<input type="checkbox" class="row-checkbox" value="${row.order_no}" data-order-id="${row.order_no}">`;
                }
            },
            { data: 'jar_no' },
            { data: 'order_no' },
            { data: 'date' },
            { data: 'billing_name', orderable: false, searchable: false },
            { data: 'shipping_name', orderable: false, searchable: false },
            { data: 'affiliate_code', orderable: false, searchable: false },
            { data: 'total_jar', orderable: false, searchable: false },
            { data: 'total_recipient', orderable: false, searchable: false },
            //{ data: 'type', orderable: false, searchable: false },
            //{ data: 'status', orderable: false, searchable: false },
            { data: 'price', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false }
        ],
        drawCallback: function () {
            initTippy();
            $('#customer-orders-table tbody tr.sub-order-row').remove();
            $('#customer-orders-table tbody tr').each(function () {
                const $subOrderRow = $('<div class="sub-order-list"></div>');
                $(this).after($subOrderRow);
            });
        },
        initComplete: function () {
            jQuery("#customer-orders-table_length").hide();

            

            const customFilter = `
              <label style="margin-left: 10px;">
                Select Year:
                <select id="select-year" class="form-control" style="width: 250px;">
                </select>
            </label>
             <label style="margin-left: 20px;">
                <label for="amount">Quantity Range:</label>
                <input type="text" id="quantity_range" readonly="" style="border:0; color:#f6931f; font-weight:bold;">
<div id="slider-range"></div>
 </label>
              <label style="margin-left: 10px; display:none" >
             affiliate :
                 <select id="select-affiliate" class="form-control" style="width: 250px;"><option value="">Select affiliate </option></select>
             </label>
             <label style="margin-left: 10px;">
             customer:
                 <select id="select-customer" class="form-control" style="width: 250px;"><option value="">Select customer</option></select>
             </label >
               <label style="margin-left: 10px;">
               Order status:
                <select id="order_status" name="order_status" class="" tabindex="-1" aria-hidden="true">
                    <option value="all">All</option><option value="wc-processing">Processing</option>
				    <option value="wc-pending">Pending payment</option><option value="wc-processing">Processing</option><option value="wc-on-hold">On hold</option><option value="wc-completed">Completed</option><option value="wc-cancelled">Cancelled</option><option value="wc-refunded">Refunded</option><option value="wc-failed">Failed</option><option value="wc-checkout-draft">Draft</option></select>
             </label>
                <label style="margin-left: 10px;">
                    Ship Type:
                    <select id="custom-order-type-filter" class="custom-select form-control">
                        <option value="all">All Ship Types</option>
                        <option value="single_address">Single Address</option>
                        <option value="multiple_address">Multiple Addresses</option>
                    </select>
                </label>
                   <label style="margin-left: 10px;">
                    PDF Export Type:
                    <select id="custom-pdf-export-type" class="custom-pdf-export-type form-control">
                        <option value="all">PDF Types</option>
                        <option value="2p">2P: Online & paper orders (print version)</option>
                        <option value="2e">2E: Online & paper orders (email version)</option>
                        <option value="4p">4P: Online & paper orders (print version)</option>
                        <option value="4e">4E: Online & paper orders (email version)</option>
                        <option value="5p">5p</option>
                    </select>
                </label>
                <label style="margin-left: 10px;" class="custom-order-status-filter-wrapper">
                    Order Status:
                    <select id="custom-order-status-filter" class="custom-select form-control">
                        <option value="all">All Status</option>
                        <option value="wc-pending">Pending payment</option>
                        <option value="wc-processing">Processing</option>
                        <option value="wc-on-hold">On hold</option>
                        <option value="wc-completed">Completed</option>
                        <option value="wc-cancelled">Cancelled</option>
                        <option value="wc-refunded">Refunded</option>
                        <option value="wc-failed">Failed</option>
                        <option value="wc-checkout-draft">Draft</option>
                    </select>
                </label>
            `;
            $('#customer-orders-table_filter').append(customFilter);
            // $('#customer-orders-table_filter').append('<label style="margin-left: 10px;">&nbsp;<div><button class="order-export-data w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Data</button></div></label>');
            $('#customer-orders-table_filter').append('<label style="margin-left: 10px;">&nbsp;<div><button class="order-pdf-export w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Pdf</button></div></label>');
 
            const tableType = `
                <label for="main_order">
                    <input type="radio" id="main_order" name="table_order_type" value="main_order" checked>
                    <span>Main Order</span>
                </label>
                <label for="sub_order_order" style="margin-left: 15px;">
                    <input type="radio" id="sub_order_order" name="table_order_type" value="sub_order_order">
                    <span>Jar Order</span>
                </label>
            `;
            $('#customer-orders-table_length').before('<div style="text-align:center; margin-bottom: 10px;">' + tableType + '</div>');
 
            toggleRecipientColumn();
 
            $('#custom-order-type-filter, #custom-order-status-filter').on('change', function (e) {
                e.preventDefault();
                table.ajax.reload();
            });
 
            $(document).on('click', 'input[name="table_order_type"]', function (e) {
                e.preventDefault();
                toggleRecipientColumn();
              //  table.ajax.reload();
            });
 
            const yearSelect = document.getElementById('select-year');
            const startYear = new Date().getFullYear();
            const endYear = 2018;
             const customYear = ''; // Optional custom year to prioritize
             const defaultSelected = 2025;

            const addedYears = new Set();
        
          // Populate years from current down to end
            for (let year = startYear; year >= endYear; year--) {
                if (!addedYears.has(year)) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    if (year === defaultSelected) option.selected = true;
                    yearSelect.appendChild(option);
                    addedYears.add(year);
                }
            }

            $( function() {
                $( "#slider-range" ).slider({
                  range: true,
                  min: 1,
                  max: 1000,
                  values: [ 1, 1000 ],
                  slide: function( event, ui ) {
                    $( "#quantity_range" ).val( ui.values[ 0 ] + " - " + ui.values[ 1 ] );
                   // table.draw(); // Redraw DataTable with new filter
                  },change: function( event, ui ) {
                    table.draw(); // ✅ fires once after sliding stops
                  }
                  
                });
                $( "#quantity_range" ).val( $( "#slider-range" ).slider( "values", 0 ) +
                  " - " + $( "#slider-range" ).slider( "values", 1 ) );
              } );

            $('#select-customer').select2({
                placeholder: 'Search',
                allowClear: true,
                ajax: {
                    url: oam_ajax.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'orthoney_get_customers_autocomplete',
                            customer: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function (item) {
                                return { id: item.id, text: item.label };
                            })
                        };
                    },
                    cache: true
                }
            });

            $('#select-affiliate').select2({
                placeholder: 'Select affiliate...',
                allowClear: true,
                ajax: {
                    url: oam_ajax.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function () {
                        return {
                            action: 'orthoney_get_used_affiliate_codes',
                            security: oam_ajax.nonce // if using nonce
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    }
                }
            });
            
            
            
 
            jQuery('.selectall-checkbox').on('change', function() {
                jQuery('.row-checkbox').prop('checked', this.checked);
              });
 
 
            // 🔄 Export data trigger (new AJAX call)
            $(document).on('click', '.order-export-data', function (e) {
                e.preventDefault();
 
                const requestData = {
                    action: 'orthoney_customer_order_export_ajax',
                    security: oam_ajax.nonce,
                    custom_order_type: $('#custom-order-type-filter').val(),
                    custom_order_status: $('#custom-order-status-filter').val(),
                    table_order_type: $('input[name="table_order_type"]:checked').val(),
                    search: {
                        value: $('#customer-orders-table_filter input').val()
                    }
                };
 
                process_group_popup('Generating CSV...');
 
                $.post(oam_ajax.ajax_url, requestData, function (response) {
                    Swal.close();
                    if (response.success && response.data?.url) {
                        const a = document.createElement('a');
                        a.href = response.data.url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        Swal.fire({
                            title: 'Export Failed',
                            text: response?.data?.message || 'Something went wrong during export.',
                            icon: 'error',
                        });
                    }
                });
            });
 
 
            $(document).on('click', '.order-pdf-export', function (e) {
                e.preventDefault();
 
 
                let selectedValues = [];
 
                $('.row-checkbox:checked').each(function () {
                    selectedValues.push($(this).val());
                });
 
                if (selectedValues.length === 0) {
                    Swal.fire({
                        title: 'No Order Selected',
                        text: 'Please check at least one order before proceeding.',
                        icon: 'warning',
                    });
                    return; // Stop further execution
                }
 
              //  console.log(selectedValues);
 
                const requestData = {
                    action: 'orthoney_customer_order_export_pdf_ajax',
                    security: oam_ajax.nonce,
                    custom_order_type: $('#custom-order-type-filter').val(),
                    custom_order_status: $('#custom-order-status-filter').val(),
                    custom_order_pdf_type: $('#custom-pdf-export-type').val(),
                    table_order_type: $('input[name="table_order_type"]:checked').val(),
                    selectedValues:selectedValues,
                    search: {
                        value: $('#customer-orders-table_filter input').val()
                    }
                };
 
                process_group_popup('Generating PDF...');
 
                $.post(oam_ajax.ajax_url, requestData, function (response) {
                    Swal.close();
                    if (response.success && response.data?.url && response.data.request == "download") {
                        const a = document.createElement('a');
                        a.href = response.data.url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
 
                        setTimeout(() => {
                            $.post(oam_ajax.ajax_url, {
                                action: 'remove_pdf_data',
                                file_url: response.data.url
                            });
                        }, 20000); // 5000ms = 5 seconds
               
 
                    } else if(response.data?.request) {
                        Swal.fire({
                            title: 'PDF file has been sent on email',
                            text: response?.data?.message || 'Something went wrong during export.',
                            icon: 'success',
                        });
                        setTimeout(() => {
                            $.post(oam_ajax.ajax_url, {
                                action: 'remove_pdf_data',
                                file_url: response.data.url
                            });
                        }, 20000); // 5000ms = 5 seconds
                       
                    } else {
                        Swal.fire({
                            title: 'Export Failed',
                            text: response?.data?.message || 'Something went wrong during export.',
                            icon: 'error',
                        });
                    }
                });
            });
 
 
            // setTimeout(function () {
            //     const searchBox = $('#customer-orders-table_filter input');
            //     searchBox.attr('placeholder', 'Search by name');
           
            //     searchBox.off('input').on('input', function () {
            //         const val = this.value.trim();
            //         const wordCount = val.split(/\s+/).filter(Boolean).length;
           
            //         if (wordCount >= 3 || val.length === 0) {
            //             table.search(val).draw();
            //         }
            //     });
            // }, 100);
 
            // function toggleRecipientColumn() {
            //     const selectedType = $('input[name="table_order_type"]:checked').val();
            //     const filterWrapper = $('.custom-order-status-filter-wrapper');
            //     const recipientCountColumnIndex = 7;
            //     const recipientNameColumnIndex = 4;
            //     const jarNoColumnIndex = 0;
 
            //     if (selectedType === 'sub_order_order') {
            //         filterWrapper.show();
            //         table.column(jarNoColumnIndex).visible(true);
            //         table.column(recipientNameColumnIndex).visible(true);
            //         table.column(recipientCountColumnIndex).visible(false);
            //     } else {
            //         filterWrapper.hide();
            //         table.column(jarNoColumnIndex).visible(false);
            //         table.column(recipientNameColumnIndex).visible(false);
            //         table.column(recipientCountColumnIndex).visible(true);
            //     }
            // }
            function toggleRecipientColumn() {
                const selectedType = $('input[name="table_order_type"]:checked').val();
                const filterWrapper = $('.custom-order-status-filter-wrapper');
                const recipientCountColumnIndex = 8;
                const recipientNameColumnIndex = 5;
                const jarNoColumnIndex = 1;

                if (selectedType === 'sub_order_order') {
                    filterWrapper.show();
                    table.column(jarNoColumnIndex).visible(true);
                    table.column(recipientNameColumnIndex).visible(true);
                    table.column(recipientCountColumnIndex).visible(false);
                } else {
                    filterWrapper.hide();
                  
                    table.column(jarNoColumnIndex).visible(false);
                    table.column(recipientNameColumnIndex).visible(false);
                    table.column(recipientCountColumnIndex).visible(true);
                }
            }

            jQuery(document).on('change', '#select-customer, #order_status, #select-year', function (e) {
                table.ajax.reload();
             });
           
        }
    });
});
 
 
 
 
jQuery(document).on('click', '.download_csv_by_order_id', function (e) {
    e.preventDefault();

    const orderid = jQuery(this).data('orderid');

    const requestData = {
        action: 'orthoney_customer_order_export_by_id_ajax',
        security: oam_ajax.nonce,
        order_id: orderid
    };

    process_group_popup('Generating CSV...');

    jQuery.ajax({
        url: oam_ajax.ajax_url,
        type: 'POST',
        data: requestData,
        success: function (response) {
            setTimeout(() => {
                Swal.close();
            }, 500);
            if (response.success && response.data?.url) {
                const a = document.createElement('a');
                a.href = response.data.url;
                a.download = response.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } else {
                Swal.fire({
                    title: 'Export Failed',
                    text: response?.data?.message || 'Something went wrong during export.',
                    icon: 'error',
                });
            }
        },
        error: function () {
            Swal.close();
            Swal.fire({
                title: 'Export Failed',
                text: 'An AJAX error occurred while exporting the order.',
                icon: 'error',
            });
        }
    });
});



document.addEventListener('click', function (event) {
    if (event.target.classList.contains('show-sub-order')) {
        const target = event.target;
        const orderid = target.getAttribute('data-orderid');
        const status = target.getAttribute('data-status');
        if(status == 0){

            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'customer_sub_order_details_ajax',
                    orderid: orderid,
                    security: oam_ajax.nonce
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    target.setAttribute('data-status', 1);
                    const tr = target.closest('tr');
                    let next = tr.nextElementSibling;
                    while (next && !next.classList.contains('sub-order-list')) {
                        next = next.nextElementSibling;
                    }

                    if (next) {
                        next.style.display = 'table-row'; // or 'block', depending on your markup
                    }
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


            

        }else{

        }

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