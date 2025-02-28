const greetingTextarea = document.getElementById("greeting");
if(greetingTextarea){
    const charCounter = document.getElementById("char-counter").querySelector('span');
    const maxChars = 250;

    greetingTextarea.addEventListener("input", () => {
        const remainingChars = maxChars - greetingTextarea.value.length;
        charCounter.textContent = `${remainingChars}`;
    });

}

document.addEventListener('lity:open', function (event) {
    const popupOverlay = document.querySelector('.lity-wrap');
    if (popupOverlay) {
        popupOverlay.addEventListener('click', function (e) {
            if (e.target.classList.contains('lity-wrap')) {
                e.stopPropagation(); // Prevents the default closing behavior
            }
        });
    }
});
document.addEventListener('lity:close', function(event) {
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

function getURLParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
  }

function getUploadData(user, groupId) {

    const newCSVData = document.getElementById("newCSVData");
    const newids = newCSVData.dataset.id || '';


    fetch(oam_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'ort_honey_get_recipient_ajax',
            user: user,
            newids: newids,
            groupId: groupId,
            security: oam_ajax.nonce
        }),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const successData = document.getElementById("successCSVData");
            const failData = document.getElementById("failCSVData");
            const duplicateData = document.getElementById("duplicateCSVData");
            

            duplicateData.innerHTML = data.data.duplicateData;
            successData.innerHTML = data.data.successData;
            failData.innerHTML = data.data.failData;
            newCSVData.innerHTML = data.data.newData;

        } else {
            console.log( 'Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        // msg.textContent = 'An error occurred while creating the group.';
    });
}
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
const deleteGroupButton = document.querySelectorAll('.deleteGroupButton');
if(deleteGroupButton.length > 0){
    deleteGroupButton.forEach(button => {
        button.addEventListener('click', function(event) {
            const target = event.target; 
            const groupID = target.closest('.recipient-group-list').querySelector('select').value;
            // Send AJAX request
            const msg = target.closest('.recipient-group-list').querySelector('.response-msg');
            msg.textContent = '';
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
                    msg.textContent = 'Group deleted successfully!';
                    target.closest('.recipient-group-list').querySelector('form').reset();
                    setTimeout(function() {
                        window.location.reload();
                    }, 300);
                } else {
                    msg.textContent = 'Error: ' + data.message;
                }
            })
            .catch(error => {
                msg.textContent = 'An error occurred while creating the group.';
            });
        });
    });
    }
/*
Deleted group Js End
 */
/*
Upload CSV Start
 */
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#csv-upload-form");
    if(form){
    const progressWrapper = document.getElementById("progress-wrapper");
    const progressBar = document.getElementById("progress-bar");
    const progressPercentage = document.getElementById("progress-percentage");
    const message = document.getElementById("message");
   
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        
        const file = form.querySelector('input[type="file"]').files[0];
        const group_name = form.querySelector('input[name="group_name"]').value;
        const greeting = form.querySelector('textarea[name="greeting"]').value;
        
        let currentChunk = 0;
        let totalRows = 0;
        let groupId = null;

        function uploadChunk() {
            const formData = new FormData();
            formData.append("action", "ort_honey_insert_recipient_ajax");
            formData.append("csv_file", file);
            formData.append("security", oam_ajax.nonce);
            formData.append("group_name", group_name);
            formData.append("greeting", greeting);
            formData.append('current_chunk', currentChunk);

            if (groupId !== null) {
                formData.append("group_id", groupId);
            }

            progressWrapper.style.display = "block";

            const xhr = new XMLHttpRequest();
            xhr.open("POST", oam_ajax.ajax_url, true);

            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // First chunk, get total rows
                        if (currentChunk === 0) {
                            totalRows = response.data.total_rows;
                            groupId = response.data.group_id;
                        }

                        // Update progress
                        const progress = response.data.progress;
                        progressBar.value = progress;
                        progressPercentage.textContent = `${progress}%`;

                        // Log any row errors
                        if (response.data.error_rows && response.data.error_rows.length > 0) {
                            console.warn('Errors in rows:', response.data.error_rows);
                        }

                        // Continue or finish
                        if (!response.data.finished) {
                            currentChunk = response.data.next_chunk;
                            uploadChunk(); // Process next chunk
                        } else {
                            
                            //getUploadData(response.data.user, groupId);
                            
                            form.querySelector('input[name="recipient_group_id"]').value = groupId;
                            
                            form.submit();
                            progressWrapper.style.display = "none";
                        }
                    } else {
                        message.innerHTML = `<p style="color: red;">${response.data.message}</p>`;
                        progressWrapper.style.display = "none";
                    }
                } else {
                    message.innerHTML = `<p style="color: red;">An error occurred while processing the request.</p>`;
                    progressWrapper.style.display = "none";
                }
            };

            xhr.onerror = function () {
                message.innerHTML = `<p style="color: red;">Network error during upload.</p>`;
                progressWrapper.style.display = "none";
            };

            xhr.send(formData);
        }

        // Start the chunked upload
        uploadChunk();
    });
    }
});

/*
Upload CSV End
 */
//
const groupsList = document.querySelectorAll('.groups-list');
if(groupsList.length > 0){
    groupsList.forEach(select => {
        select.addEventListener('change', function(event) {
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
        
        const duplicateCSVData = document.querySelector('#duplicateCSVData');
        const groups = duplicateCSVData.querySelectorAll('.group-header');

        const ids = []; // Store all IDs

        groups.forEach(function (group) {
            const groupId = group.getAttribute('data-group');
            const dataGroupTrs = duplicateCSVData.querySelectorAll(
                'tr[data-group="' + groupId + '"]:not(.group-header)'
            );

            let selectedData = Array.from(dataGroupTrs).find(data => data.getAttribute('data-verify') == "1");

            // Fallback to data-verify="0" if no data-verify="1"
            if (!selectedData) {
                selectedData = Array.from(dataGroupTrs).find(data => data.getAttribute('data-verify') == "0");
            }

            const firstId = selectedData ? selectedData.getAttribute('data-id') : null;

            // Collect all other IDs except the first
            const remainingIds = Array.from(dataGroupTrs)
                .map(data => data.getAttribute('data-id'))
                .filter(id => id !== firstId);

            ids.push(...remainingIds);
        });


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
                        const form = document.querySelector("#csv-upload-form");
                        let group_id ='';
                        if(form){
                            group_id = form.querySelector('input[name="recipient_group_id"]').value;
                        }else{
                            group_id = getURLParam('recipient_group_id');
                        }
                        getUploadData(data.data.user, group_id);

                        Swal.fire({
                            title: data.data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });
                        
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

        let groupId = 0;
        let count = 0;
        let groupHeader= '';
        const target = event.target;

        const recipientTr = target.closest('tr');

        
        groupId = recipientTr.getAttribute('data-group');
        if (groupId !== null && groupId !== '0' && groupId !== '') {
            groupHeader = document.querySelector('.group-header[data-group="' + groupId + '"]');
            if(groupHeader){
                count = groupHeader.getAttribute('data-count');
            }
        }else{
            groupId = getURLParam('recipient_group_id');
        }
        const recipientID = recipientTr?.getAttribute('data-id');

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
            text: 'Remove this recipient',
            icon: 'warning',
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
                        groupId: groupId,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {

                        // if (data.data.groupId != 0) {
                        //     groupHeader.setAttribute('data-count', count - 1);
                        //     if ((count - 1) == 1) {
                        //         const lastTr = document.querySelector('tr[data-group="' + data.data.groupId + '"]:not(.group-header)');
                        //         if (lastTr) {
                        //             // getUploadData(data.data.user);
                        //         }
                        //     }
                        // }

                        let group_id = '';
                        const form = document.querySelector("#csv-upload-form");
                        if(form){
                            group_id = form.querySelector('input[name="recipient_group_id"]').value;
                            getUploadData(data.data.user, group_id);
                        }else{

                            getUploadData(data.data.user, getURLParam('recipient_group_id'));
                        }
                        
                        Swal.fire({
                            title: 'Recipient removed successfully!',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });
                        
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

if (recipientManageForm) {
    recipientManageForm.addEventListener("submit", function (e) {
        e.preventDefault(); // Prevent form submission

        const form = document.querySelector("#csv-upload-form");
        let group_id = '';
        if(form){
            group_id = form.querySelector('input[name="recipient_group_id"]').value;
        }
        let groupId = getURLParam('recipient_group_id');
        if (groupId !== null && groupId !== '' && groupId !== '0') {
            group_id = groupId;
        }
        const formData = new FormData(this);

        // Append action to the form data
        formData.append('action', 'manage_recipient_form'); 
        formData.append('group_id', group_id); 

        // Perform the AJAX request
        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())  // Parse the response as JSON
        .then(data => {
            if (data.success) {
                if (data.data.status === 'new') {
                    const newData = document.querySelector("#newCSVData");
                    if (newData) {
                        const existingDataId = newData.dataset.id || '';
                        newData.dataset.id = existingDataId
                            ? `${existingDataId},${data.data.recipient_id}`
                            : `${data.data.recipient_id}`;
                    }
                }

                // Show success alert using SweetAlert2
                Swal.fire({
                    title: data.data.message, // Get message from the response
                    icon: 'success',
                    timer: 2500,
                    showConfirmButton: false,
                    timerProgressBar: true
                }).then(() => {
                    // Reset the form after success
                    recipientManageForm.reset();
                    recipientManageForm.querySelector('#recipient_id').value = '';
                });

                // Close modal if lity is used
                
                const currentLity = document.querySelector('[data-lity-close]');
                if (currentLity) {
                    currentLity.click();
                }
                let group_id ='';
                const form = document.querySelector("#csv-upload-form");
                if(form){
                    group_id = form.querySelector('input[name="recipient_group_id"]').value;
                }else{
                    group_id = getURLParam('recipient_group_id');
                }
                getUploadData(data.data.user, group_id);
                
            } else {
                // Show error message using SweetAlert2
                Swal.fire({
                    title: 'Error',
                    text: data.data.message || 'Failed to update recipient details.11111',
                    icon: 'error',
                });
            }
        })
        .catch(error => {
            // Handle any errors during the fetch request
            console.error('Error during AJAX request:', error);
            Swal.fire({
                title: 'Error',
                text: 'An error occurred while processing the request.1111',
                icon: 'error',
            });
        });
    });
}


/*
Edit and add Recipient form JS END
 */

/*
Edit button JS start
 */

document.addEventListener('click', function (event) {
    if (event.target.classList.contains('editRecipient')) {

        const target = event.target;

        event.preventDefault();

        const recipientTr = target.closest('tr');

        if(recipientTr){
            const recipientID = recipientTr.getAttribute('data-id');

            fetch(oam_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_recipient_base_id',
                    id: recipientID,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {

                    const id = data.data.id;
                    const first_name = data.data.first_name;
                    const last_name = data.data.last_name;
                    const address_1 = data.data.address_1;
                    const address_2 = data.data.address_2;
                    const city = data.data.city;
                    const state = data.data.state;
                    const country = data.data.country;
                    const zipcode = data.data.zipcode;
                    
                    const form = document.querySelector('#recipient-manage-form form');
                    form.querySelector('#recipient_id').value = id;
                    form.querySelector('#first_name').value = first_name;
                    form.querySelector('#last_name').value = last_name;
                    form.querySelector('#address_1').value = address_1;
                    form.querySelector('#address_2').value = address_2;
                    form.querySelector('#city').value = city;
                    form.querySelector('#state').value = state;
                    form.querySelector('#country').value = country;
                    form.querySelector('#zipcode').value = zipcode;
                    setTimeout(function() {
                        lity(event.target.getAttribute('data-popup'));
                    }, 250);
                    
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
        }
    }
});




/*
Edit button JS END
*/

document.addEventListener('click', function (event) {
    // Check if the clicked element has the class 'download-csv'
    if (event.target.id === 'download-failed-recipient-csv') {

        // Prevent the default behavior (like form submission)
        event.preventDefault();

        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', oam_ajax.ajax_url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Create a temporary link to download the CSV file
                    const a = document.createElement('a');
                    a.href = response.data.url;
                    a.download = response.data.filename; // Set filename
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        };
        // Sending the AJAX request with the action 'download_failed_recipient'
        xhr.send('action=download_failed_recipient');
    }
});
