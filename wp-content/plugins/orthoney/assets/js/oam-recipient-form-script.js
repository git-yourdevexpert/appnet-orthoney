function getURLParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(param);
}

document.addEventListener('DOMContentLoaded', function() {
  const multiStepForm = document.querySelector('#multiStepForm');

  if (multiStepForm) {
    let currentStep = 0;
    const steps = document.querySelectorAll('.step');
    const stepNavItems = document.querySelectorAll('.step-nav-item');
    const pid = document.querySelector('#multiStepForm #pid');
    const singleAddress = document.querySelector('.single-address-order');
    const multipleAddress = document.querySelector('.multiple-address-order');

    let activeStepItem = document.querySelector('.step-nav-item.active');
    if (activeStepItem) {
      let stepIndex = parseInt(activeStepItem.getAttribute('data-step'), 10);
      if (!isNaN(stepIndex)) {
        showStep(stepIndex);
        currentStep = stepIndex;
      } else {
        showStep(currentStep);
      }
    }

    document.querySelectorAll('#multiStepForm a').forEach(a => {
      a.addEventListener('click', function(event) {
        event.preventDefault();
      });
    });

    document.addEventListener('click', function(event) {
      if (event.target.id === 'singleAddressCheckout') {
        event.preventDefault(); // Only prevent default for this specific button
        processStepSaveAjax(pid?.value || "0", 5);
        singleAddressDataSaveAjax();
      }
    });
    

    document.querySelectorAll('#multiStepForm .next').forEach(button => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        console.log(currentStep);

        const uploadTypeOutput = document.querySelector('input[name="upload_type_output"]:checked');
        const deliveryPreference = document.querySelector('input[name="delivery_preference"]:checked');

        if (currentStep !== 0) {
          if (deliveryPreference) {
            console.log('1');
            // console.log(uploadTypeOutput.value);
            if (validateCurrentStep() && currentStep === 1) {
              if (deliveryPreference.value !== 'single_address') {
                console.log('2');
                currentStep += (uploadTypeOutput.value === 'add-manually' || uploadTypeOutput.value === 'select-group') ? 2 : 1;
                // currentStep = Math.min(currentStep, steps.length - 1);
                console.log('currentStep', currentStep);
                if (uploadTypeOutput.value === 'add-manually') {
                  addRecipientManuallyPopup(1);
                }
              } else {
                console.log('3');
                currentStep = Math.max(currentStep, steps.length - 1);
              }
              if (
                uploadTypeOutput &&
                uploadTypeOutput.value !== '' &&
                (uploadTypeOutput.value !== 'add-manually' || deliveryPreference.value !== 'single_address')
              ) {
                showStep(currentStep);
              }

              processDataSaveAjax(pid?.value || "0", currentStep);
            }
          } else {
            console.log('4');
            if (currentStep !== 1) {
              currentStep++;
              showStep(currentStep);
              processDataSaveAjax(pid?.value || "0", currentStep);
            }
          }
        } else {
          console.log('5');
          currentStep++;
          showStep(currentStep);
          processDataSaveAjax(pid?.value || "0", currentStep);
        }
      });
    });

    document.querySelectorAll('#multiStepForm input[name="upload_type_output"]').forEach(input => {
      input.addEventListener('click', function(event) {
        // event.preventDefault();
        const groups_wrapper = document.querySelector('.multiple-address-order .groups-wrapper');
        const groups_select = groups_wrapper.querySelector('select');
        if (this.value == 'select-group') {
          groups_wrapper.style.display = 'block';
          groups_select.setAttribute('required', 'required');
        } else {
          groups_wrapper.style.display = 'none';
          groups_select.removeAttribute('required');
        }
      });
    });

    document.querySelectorAll('#checkout_proceed_with_only_verified_addresses').forEach(button => { 
      button.addEventListener('click', async function(event) {  
          event.preventDefault();
          const form = document.querySelector("#multiStepForm");
          event.target.closest('div').querySelector('input[name="checkout_proceed_with_multi_addresses_status"]').value = "only_verified";
          let html = "<p>Are you sure you want to proceed with only verified addresses?</p>";
          const processCheckoutStatus = document.querySelector('input[name="processCheckoutStatus"]');
          const delivery_preference = document.querySelector('input[name="delivery_preference"]:checked');
          const checkout_proceed_with_multi_addresses_status = document.querySelector('input[name="checkout_proceed_with_multi_addresses_status"]');
          
          if (processCheckoutStatus) {

            if (processCheckoutStatus.value == 5 && delivery_preference.value == 'multiple_address' && checkout_proceed_with_multi_addresses_status.value == 'only_verified') {
              await process_to_checkout_ajax_part(form, 1);
            }else{

              await process_to_checkout(form, html, 1);
            }
          }
      });
  });
  
  document.querySelectorAll('#checkout_proceed_with_only_unverified_addresses').forEach(button => { 
      button.addEventListener('click', async function(event) {  
          event.preventDefault();
          const form = document.querySelector("#multiStepForm");
          let html = "<p>Are you sure you want to proceed with unverified addresses?</p>";

          const processCheckoutStatus = document.querySelector('input[name="processCheckoutStatus"]');
          const delivery_preference = document.querySelector('input[name="delivery_preference"]:checked');
          const checkout_proceed_with_multi_addresses_status = document.querySelector('input[name="checkout_proceed_with_multi_addresses_status"]');
          
          if (processCheckoutStatus) {

            if (processCheckoutStatus.value == 5 && delivery_preference.value == 'multiple_address' && checkout_proceed_with_multi_addresses_status.value == 'only_verified') {
              await process_to_checkout_ajax_part(form, 0);
            }else{

              await process_to_checkout(form, html, 0);
            }
          }
          
      });
  });
  
  async function process_to_checkout(form, html, status) { 
    try {
        const result = await Swal.fire({
            title: '',
            html: html,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Proceed',
            cancelButtonText: 'No, I Want to Add/Edit Records.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: false,
            width: '650px',
        });

        if (!result.isConfirmed) return; // Exit if user cancels

        if (typeof process_group_popup === "function") {
            process_group_popup(); // Ensure the function exists
        }

        
        if (!form) {
            Swal.fire("Error", "Form not found!", "error");
            return;
        }

        await process_to_checkout_ajax_part(form, status);

    } catch (error) {
        console.error("AJAX error:", error);
        Swal.fire("Error", `Request failed: ${error.message || "Unknown error"}`, "error");
    }
}

  async function process_to_checkout_ajax_part(form, status) { // Ensure async
    try {
        const formData = new FormData(form);
        formData.append("action", "orthoney_process_to_checkout_ajax");
        formData.append("currentStep", typeof currentStep !== "undefined" ? currentStep : "");
        formData.append("security", oam_ajax.nonce);
        formData.append("status", status); // Use the passed status

        const response = await fetch(oam_ajax.ajax_url, {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const responseData = await response.json();

        if (!responseData.success) {
            Swal.fire("Error", responseData.message || "An error occurred", "error");
            return;
        }

        Swal.fire({
            title: "Please wait while we process your order properly.",
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
        });

        setTimeout(() => {
            window.location.href = responseData.data.checkout_url;
        }, 1500);
        
    } catch (error) {
        console.error("Fetch error:", error);
        Swal.fire("Error", `Request failed: ${error.message || "Unknown error"}`, "error");
    }
}
  




    document.querySelectorAll('.keep_this_and_delete_others').forEach(button => { 
      button.addEventListener('click', function(event) {
          event.preventDefault();
  
          // Find the closest <tr> to the clicked button
          let clickedRow = this.closest('tr');
          let clickedRowId = clickedRow.getAttribute('data-id');
  
          // Find the group header above the clicked row
          let groupHeader = clickedRow.previousElementSibling;
          while (groupHeader && !groupHeader.classList.contains('group-header')) {
              groupHeader = groupHeader.previousElementSibling;
          }
  
          if (groupHeader) {
              let groupId = groupHeader.getAttribute('data-group');
  
              // Collect all data-id values in the group
              let allIds = [];
              let currentRow = groupHeader.nextElementSibling;
  
              while (currentRow && !currentRow.classList.contains('group-header')) {
                  if (currentRow.hasAttribute('data-id')) {
                      allIds.push(currentRow.getAttribute('data-id'));
                  }
                  currentRow = currentRow.nextElementSibling;
              }
  
              // Remove the clicked row's ID from the array (to keep it)
              let filteredIds = allIds.filter(id => id !== clickedRowId);
  
              Swal.fire({
                title: 'Are you sure?',
                text: 'Keep this and delete others',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel',
                allowOutsideClick: true
            }).then((result) => {
                if (result.isConfirmed) {
                  process_group_popup();
                    fetch(oam_ajax.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'keep_this_and_delete_others_recipient',
                            delete_ids: filteredIds,
                            security: oam_ajax.nonce,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            
                            Swal.fire({
                                title: 'Recipient marge successfully!',
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
                                text: data.data.message || 'Failed to marge recipient.',
                                icon: 'error',
                            });
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            title: 'Error',
                            text: 'An error occurred while margining the recipient.',
                            icon: 'error',
                        });
                    });
                }
            });

          }
      });
  });
  

    document.querySelectorAll('#multiStepForm .editProcessName').forEach(button => { 
      button.addEventListener('click', function(event) {
          event.preventDefault();
          const process_name = event.target.getAttribute('data-name'); // Get process name
  
          Swal.fire({
            title: "Enter group name",
            text: "The group name will be used for the future.",
            input: "text",
            inputPlaceholder: "Enter group name",
            inputAttributes: {
                autocapitalize: "off"
            },
            inputValue: process_name,
            showCancelButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            confirmButtonText: "Save and Continue",
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: true,
            preConfirm: async (groupName) => {
                if (!groupName) {
                    Swal.showValidationMessage("Group name is required!");
                  }
                  process_group_popup();
                  fetch(oam_ajax.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                      action: 'edit_process_name',
                      group_name: groupName,
                      security: oam_ajax.nonce,
                      pid : getURLParam('pid')
                    }),
                  })
                  .then(response => response.json())
                  .then(data => {
                    if (data.success) {
                      const editProcessName = document.querySelector('.editProcessName');
                      editProcessName.setAttribute('data-name', groupName);
                      editProcessName.closest('p').querySelector('strong').innerHTML = groupName;
                      Swal.fire({
                        title: data.data.message,
                        icon: 'success',
                        timer: 2500,
                        showConfirmButton: false,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        allowEnterKey: false,
                        showConfirmButton: false,
                      });
        
                      
                    } else {
                      Swal.fire({
                        title: 'Error',
                        text: data.data?.message || 'Failed to remove recipient.',
                        icon: 'error',
                      });
                    }
                  })
                  .catch(error => {
                    Swal.fire({
                      title: 'Error',
                      text: 'An error occurred while removing the recipient.',
                      icon: 'error',
                    });
                  });   
            }
        });
  
      });
  });
  

    document.querySelectorAll('#multiStepForm .back').forEach(button => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        currentStep = Math.max(0, currentStep - 1);
        showStep(currentStep);
        processStepSaveAjax(pid?.value || "0", currentStep);
      });
    });

    document.querySelectorAll('.save-data').forEach(button => {
      button.addEventListener('click', function(event) {
        event.preventDefault();
        collectFormData();
      });
    });

    document.querySelectorAll('.verifyRecipientAddressButton').forEach(button => {
      button.addEventListener('click', async function(event) { // Add 'async' here for await to work
        event.preventDefault();

        const totalCount = event.target.getAttribute('data-totalcount');
        const successCount = event.target.getAttribute('data-successcount');
        const failCount = event.target.getAttribute('data-failcount');
        const duplicateCount = event.target.getAttribute('data-duplicatecount');

        let html = `<p>Out of the ${totalCount} records uploaded via CSV, `;
        if (successCount != 0) {
          html += `${successCount} were successfully added. `;
        }
        if (failCount != 0 && duplicateCount != 0) {
          html += 'However, ';
        }
        if (failCount != 0) {
          html += `${failCount} records failed to upload. `;
        }
        if (duplicateCount != 0) { // Fix this condition to correctly check duplicate count
          html += `${duplicateCount} repeated orders. </p>`;
        }
        html += `<p>Please confirm if you would like to proceed with the successfully added records.</p>`;

        const result = await Swal.fire({
          title: '',
          html: html,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, Proceed With Repeat and Successful Records.',
          cancelButtonText: 'No, I Want to Add/Edit Records.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false,
          reverseButtons: true,
          width: '650px',
        });

        if (result.isConfirmed) {
          process_group_popup();

          const form = document.querySelector("#multiStepForm");
          const formData = new FormData(form);
          formData.append("action", "orthoney_order_step_process_completed_ajax");
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

            const responseData = await response.json();

            if (!responseData.success) {
              Swal.fire("Error", responseData.message || "An error occurred", "error");
              return;
            }

            Swal.fire({
              title: "Verification is successfully!",
              icon: 'success',
              timer: 2000,
              showConfirmButton: false,
              timerProgressBar: true,
              allowOutsideClick: false,
              allowEscapeKey: false,
              allowEnterKey: false,
              showConfirmButton: false,
            });

            setTimeout(function() {
              window.location.reload();
            }, 1000);

          } catch (error) {
            console.error("AJAX error:", error);
            Swal.fire("Error", `Request failed: ${error.message || "Unknown error"}`, "error");
          }
        }
      });
    });

    document.querySelectorAll('#multiStepForm .reverifyAddress').forEach(reverifyAddress => {
      reverifyAddress.addEventListener('click', function(event) {
        event.preventDefault();
        process_group_popup('Please wait, the address verification is in progress.');

        const target = event.target;
        const recipientTr = target.closest('tr');
        const recipientID = recipientTr?.getAttribute('data-id');

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              action: 'reverify_address_recipient',
              id: recipientID,
              security: oam_ajax.nonce
            }),
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                title: data.data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
              });

              setTimeout(() => window.location.reload(), 1500);
            } else {
              Swal.fire({
                title: 'Error',
                text: data.data?.message || 'Failed to remove recipient.',
                icon: 'error',
              });
            }
          })
          .catch(error => {
            Swal.fire({
              title: 'Error',
              text: 'An error occurred while removing the recipient.',
              icon: 'error',
            });
          });
      });
    });

    const nextButton = document.getElementById('nextButton');

    if (nextButton) {
      nextButton.addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.querySelector("#multiStepForm");
        csv_upload(form);
      });
    }

    document.querySelectorAll('input[name="delivery_preference"]').forEach(function(radio) {
      radio.addEventListener('click', function(event) {
        // event.preventDefault();
        if (!singleAddress || !multipleAddress) return;
        const singleInput = singleAddress.querySelector('input[name="single_address_quantity"]');
        const multipleInput = multipleAddress.querySelector('select[name="groups"]');
        if (this.value === 'single_address') {
          this.closest('.step').querySelector('button.next').setAttribute('value', 'single_address');
          singleAddress.style.display = 'block';
          multipleAddress.style.display = 'none';
          if (multipleInput) {
            multipleInput.style.border = '';
            const errorMessage = multipleInput.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('error-message')) {
              errorMessage.innerHTML = "";
            }
            multipleInput.removeAttribute('required');
          }
          if (singleInput) singleInput.setAttribute('required', 'required');

        }
        if (this.value === 'multiple_address') {
          this.closest('.step').querySelector('button.next').removeAttribute('value');
          singleAddress.style.display = 'none';
          multipleAddress.style.display = 'block';
          if (singleInput) {
            singleInput.style.border = '';
            const errorMessage = singleInput.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('error-message')) {
              errorMessage.innerHTML = "";
            }
            singleInput.removeAttribute('required');
          }
          if (multipleInput) multipleInput.setAttribute('required', 'required');
        }
      });
    });

    document.addEventListener('click', function(event) {
      if (event.target.classList.contains('submit_csv_file')) {
        event.preventDefault();
        console.log('CSV 1');
        if (validateCurrentStep()) {
          console.log('CSV 2');
          showStep(currentStep);

          processDataSaveAjax(pid?.value || "0", currentStep);

          const form = document.querySelector("#multiStepForm");
          csv_upload(form);
        }
      }
    });

    function showStep(index) {
      steps.forEach((step, i) => step.style.display = (i === index) ? 'block' : 'none');
      stepNavItems.forEach((navItem, i) => {
        navItem.classList.toggle('active', i === index);
      });
    }

    function process_group_popup(selectHtml = '') {
      if (selectHtml == '') {
        selectHtml = 'Please wait while we process your request.';
      }
      Swal.fire({
        title: 'Processing...',
        text: selectHtml,
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    }

    function csv_upload(form) {
      if (form) {
        const file = form.querySelector('input[type="file"]').files[0];
        if (!file) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: 'Please select a file to upload!',
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
        let pid = getURLParam('pid');

        // Start processing after a slight delay to ensure UI update
        setTimeout(() => {
          function uploadChunk() {
            const formData = collectFormData();
            formData.append("action", "orthoney_insert_temp_recipient_ajax");
            formData.append("security", oam_ajax.nonce);
            formData.append("currentStep", currentStep);
            formData.append("current_chunk", currentChunk);

            if (pid !== null) {
              formData.append("pid", pid);
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", oam_ajax.ajax_url, true);

            xhr.onload = function() {
              if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                  if (currentChunk === 0) {
                    totalRows = response.data.total_rows;
                    pid = response.data.pid;

                    // Show progress bar popup only after a successful start
                    Swal.fire({
                      title: 'Processing...',
                      html: `
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
                  document.getElementById("progress-bar").style.width = progress + "%";
                  document.getElementById("progress-text").innerText = progress + "%";

                  if (!response.data.finished) {
                    currentChunk = response.data.next_chunk;
                    uploadChunk();
                  } else {
                    Swal.fire({
                      icon: 'success',
                      title: 'Upload Complete!',
                      showConfirmButton: false,
                      timer: 4500
                    });
                    setTimeout(() => {
                      window.location.reload();
                    }, 1000);
                  }
                } else {
                  Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: response.data.message
                  });
                }
              } else {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'An error occurred while processing the request.'
                });
              }
            };

            xhr.onerror = function() {
              Swal.fire({
                icon: 'error',
                title: 'Network Error',
                text: 'A network error occurred during upload.'
              });
            };

            xhr.send(formData);
          }

          uploadChunk(); // Start the upload process
        }, 500); // Slight delay to ensure popup is shown first
      }
    }

    function processStepSaveAjax(process_value, currentStep) {
      fetch(oam_ajax.ajax_url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'orthoney_order_step_process_ajax',
            process_value: process_value,
            currentStep: currentStep,
            security: oam_ajax.nonce
          })
        }).then(response => response.json()).then(data => {
          if (data.success) {
            pid.value = data.data.pid;

            console.log(data.data.pid);
            const basePath = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('pid', data.data.pid);

            const newUrl = `${basePath}?${urlParams.toString()}`;
            history.pushState(null, '', newUrl);
          }
          if (data.success) pid.value = data.data.pid;
        })
        .catch(error => console.error('Fetch error:', error));
    }

    function checkAddressMissingField() {
      let requiredFields = {
        single_order_address_1: "Address Line 1",
        single_order_city: "City",
        single_order_state: "State",
        single_order_zipcode: "Zip Code"
      };

      let missingFields = [];

      for (let field in requiredFields) {
        let input = document.getElementById(field);
        let errorMessageElement = input ? input.parentElement.querySelector('.error-message') : null;

        if (!input || input.value.trim() === "") {
          missingFields.push(requiredFields[field]);

          if (errorMessageElement) {
            errorMessageElement.textContent = input.dataset.errorMessage || `Please enter ${requiredFields[field]}.`;
            errorMessageElement.style.color = 'red';
          }
        } else {
          // ZIP code validation for the US
          if (field === "single_order_zipcode") {
            let zipCode = input.value.trim();
            let usZipRegex = /^\d{5}(-\d{4})?$/; // Matches 5-digit ZIP or ZIP+4 (e.g., 12345 or 12345-6789)
            if (!usZipRegex.test(zipCode)) {
              missingFields.push("Valid Zip Code");
              if (errorMessageElement) {
                errorMessageElement.textContent = "Please enter a valid US ZIP code (e.g., 12345 or 12345-6789).";
                errorMessageElement.style.color = 'red';
              }
            } else {
              // Clear ZIP error message if valid
              if (errorMessageElement) {
                errorMessageElement.textContent = "";
              }
            }
          } else {
            // Clear error message if the field is correctly filled
            if (errorMessageElement) {
              errorMessageElement.textContent = "";
            }
          }
        }
      }

      return missingFields.length > 0 ? missingFields : true;
    }

    function singleAddressDataSaveAjax() {
      let result = checkAddressMissingField();

      if (result === true) {
        process_group_popup('Please wait while we verify the address');

        const formData = collectFormData();
        formData.append('action', 'orthoney_single_address_data_save_ajax');
        formData.append('security', oam_ajax.nonce);

        fetch(oam_ajax.ajax_url, {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              let message = data.data.message;
              const processCheckoutStatus = document.querySelector('input[name="processCheckoutStatus"]');
              const delivery_preference = document.querySelector('input[name="delivery_preference"]');

              if (processCheckoutStatus && processCheckoutStatus.value == 5 && delivery_preference.value == 'single_address') {
                message = 'Order is preparing.';
              }

              Swal.fire({
                title: message,
                icon: 'success',
                timer: 2500,
                showConfirmButton: false,
                timerProgressBar: true
              });

              // Redirect to checkout after a delay (uncomment if needed)
              setTimeout(() => {
                window.location.href = data.data.checkout_url;
              }, 1500);
            } else {
              console.log(data.data.message);
              Swal.fire({
                title: 'Error',
                text: data.data.message || 'Error fetching address validation.',
                icon: 'error'
              });
            }
          })
          .catch(error => console.error('Fetch error:', error));
      } else {
        // Swal.fire({
        //     title: 'Error',
        //     text: "The following fields are missing:\n" + result.join("\n"),
        //     icon: 'error'
        // });
      }
    }

    function processDataSaveAjax(pid, currentStep) {
      const formData = collectFormData();
      formData.append('action', 'orthoney_order_process_ajax');
      if (pid == '') {
        pid = getURLParam('pid');
      }
      formData.append('pid', pid);
      formData.append('currentStep', currentStep);
      formData.append('security', oam_ajax.nonce);

      fetch(oam_ajax.ajax_url, {
          method: 'POST',
          body: formData
        }).then(response => response.json()).then(data => {
          if (data.success) {
            const pid = document.querySelector('#multiStepForm #pid');
            pid.value = data.data.pid;

            const basePath = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('pid', data.data.pid);

            const newUrl = `${basePath}?${urlParams.toString()}`;
            history.pushState(null, '', newUrl);
            if (data.data.step == 4) {
              setTimeout(function() {
                window.location.reload();
              }, 1000);
            }
          }
        })
        .catch(error => console.error('Fetch error:', error));
    }

    function collectFormData() {
      const form = document.querySelector('#multiStepForm');
      const formData = new FormData(form);
      return formData;
    }

    function validateCurrentStep() {
      const currentInputs = steps[currentStep].querySelectorAll('[required]');
      let isValid = true;
      currentInputs.forEach(input => {
        const errorMessage = input.parentElement.querySelector('.error-message');
        if (!input.value) {
          input.style.border = '1px solid red';
          if (errorMessage) errorMessage.textContent = input.getAttribute('data-error-message') || 'This field is required.';
          isValid = false;
        } else {
          input.style.border = '';
          if (errorMessage) errorMessage.textContent = '';
        }
      });
      return isValid;
    }

    let result = checkAddressMissingField();

    if (result === true) {
      const processCheckoutStatus = document.querySelector('input[name="processCheckoutStatus"]');
      const delivery_preference = document.querySelector('input[name="delivery_preference"]');
      
      if (processCheckoutStatus) {
        if (processCheckoutStatus.value == 5 && delivery_preference.value == 'single_address') {
          let button = document.querySelector("#multiStepForm button#singleAddressCheckout");
          if (button) {
            button.click();
          }
        }

      }
    }

    addRecipientManuallyPopup(0);

    const fileInput = document.getElementById("fileInput");
    const fileUrl = fileInput.getAttribute("value"); // Get file URL from value attribute

    if (fileUrl) {
      fetch(fileUrl)
        .then(response => response.blob())
        .then(blob => {
          const fileName = fileUrl.split("/").pop(); // Extract filename from URL
          const file = new File([blob], fileName, { type: "text/csv" });

          // Create a DataTransfer object to set file input value
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          fileInput.files = dataTransfer.files;

          // console.log("File re-uploaded:", file.name);
        })
        .catch(error => console.error("Error fetching the file:", error));
    }
  }
});


function addRecipientManuallyPopup(reload){

  const processCheckoutStatus = document.querySelector('input[name="processCheckoutStatus"]');
  const delivery_preference = document.querySelector('input[name="delivery_preference"]:checked');
  const checkout_proceed_with_multi_addresses_status = document.querySelector('input[name="checkout_proceed_with_multi_addresses_status"]');

  if (processCheckoutStatus && checkout_proceed_with_multi_addresses_status) {

    if (processCheckoutStatus.value == 5 && delivery_preference.value == 'multiple_address' && checkout_proceed_with_multi_addresses_status.value == 'only_verified') {
      let button = document.querySelector("#multiStepForm button#checkout_proceed_with_only_verified_addresses");
      if (button) {
        button.click();
      }
    }

  }

  if(reload == 1){
    setTimeout(function() {
      window.location.reload();
    }, 500);
  }
  console.log('check 1');
  let emptyDivs = [];
  const step_nav = document.querySelector('.step-nav-item.active');
  if(step_nav.getAttribute('data-step') == 3){
  ["failCSVData", "successCSVData", "duplicateCSVData", "newCSVData"].forEach(id => {
      let div = document.getElementById(id);
      if (div && div.innerHTML.trim() === "") {
          emptyDivs.push(id);
      }
  });
  console.log(emptyDivs.length);
  const upload_type_output = document.querySelector('input[name="upload_type_output"]:checked');
  if (emptyDivs.length === 4 && upload_type_output.value == 'add-manually') {
      let button = document.querySelector("#multiStepForm button.editRecipient");
      if (button) {
          button.click();
      }
  }}
}