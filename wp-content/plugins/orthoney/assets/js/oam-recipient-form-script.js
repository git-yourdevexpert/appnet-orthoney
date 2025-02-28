function getURLParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(param);
}

document.addEventListener('DOMContentLoaded', function () {
  let currentStep = 0;
  const steps = document.querySelectorAll('.step');
  const stepNavItems = document.querySelectorAll('.step-nav-item');
  const process_id = document.querySelector('#multiStepForm #process_id');
  
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

  function showStep(index) {
    steps.forEach((step, i) => step.style.display = (i === index) ? 'block' : 'none');
    stepNavItems.forEach((navItem, i) => {
      navItem.classList.toggle('active', i === index);
    });
  }

  document.querySelectorAll('.next').forEach(button => {
    button.addEventListener('click', function() {
      if (this.value !== 'single-address' && validateCurrentStep()) {
        currentStep = this.value === 'add-manually' || this.value === 'select-group' ? currentStep + 2 : currentStep + 1;
        currentStep = Math.min(currentStep, steps.length - 1);
        showStep(currentStep);
        processDataSaveAjax(process_id?.value || "0", currentStep);
      }
    });
  });

  document.querySelectorAll('.back').forEach(button => {
    button.addEventListener('click', function () {
      currentStep = Math.max(0, currentStep - 1);
      showStep(currentStep);
      processStepSaveAjax(process_id?.value || "0", currentStep);
    });
  });

  document.querySelectorAll('.save-data').forEach(button => {
    button.addEventListener('click', function () {
      collectFormData();
    });
  });

   document.querySelector('.submit_csv_file').addEventListener('click', function () {
    if (validateCurrentStep()) {
     
      showStep(currentStep);
      
      processDataSaveAjax(process_id?.value || "0", currentStep);
      openPopup();
      
    }
  });

  document.getElementById('nextButton').addEventListener('click', function (e) {
    e.preventDefault();
    
    const form = document.querySelector("#multiStepForm");
    const uploadButton = document.querySelector("#nextButton");
    csv_upload(form, uploadButton);

  });
  document.getElementById('cancelButton').addEventListener('click', function (e) {
    e.preventDefault();
    closePopup();

  });
  function openPopup() {
    document.getElementById('popupModal').style.display = 'flex';
  }

  function closePopup() {
    document.getElementById('popupModal').style.display = 'flex';
  }

  function processStepSaveAjax(process_value, currentStep) {
    fetch(oam_ajax.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'ort_honey_order_step_process_ajax',
        process_value: process_value,
        currentStep: currentStep,
        security: oam_ajax.nonce
      })
    }).then(response => response.json()).then(data => {
      if (data.success) process_id.value = data.data.process_id;
    }).catch(error => console.error('Fetch error:', error));
  }

  function processDataSaveAjax(process_value, currentStep) {
    const formData = collectFormData();
    formData.append('action', 'ort_honey_order_process_ajax');
    formData.append('process_value', process_value);
    formData.append('currentStep', currentStep);
    formData.append('security', oam_ajax.nonce);

    fetch(oam_ajax.ajax_url, {
      method: 'POST',
      body: formData
    }).then(response => response.json()).then(data => {
      if (data.success) process_id.value = data.data.process_id;
    }).catch(error => console.error('Fetch error:', error));
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




  function csv_upload(form, uploadButton){
  if (form && uploadButton) {
      const progressWrapper = document.getElementById("progress-wrapper");
      const progressBar = document.getElementById("progress-bar");
      const progressPercentage = document.getElementById("progress-percentage");
      const message = document.getElementById("message");

      uploadButton.addEventListener("click", function () {
          const file = form.querySelector('input[type="file"]').files[0];
          const group_name = form.querySelector('input[name="csv_name"]').value;
          const greeting = form.querySelector('textarea[name="greeting"]').value;
          
          if (!file) {
              message.innerHTML = `<p style="color: red;">Please select a file to upload.</p>`;
              return;
          }

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
              formData.append("current_chunk", currentChunk);

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
                          if (currentChunk === 0) {
                              totalRows = response.data.total_rows;
                              groupId = response.data.group_id;
                          }

                          // Update progress
                          const progress = response.data.progress;
                          progressBar.value = progress;
                          progressPercentage.textContent = `${progress}%`;

                          // Log row errors
                          if (response.data.error_rows && response.data.error_rows.length > 0) {
                              console.warn("Errors in rows:", response.data.error_rows);
                          }

                          // Continue or finish
                          if (!response.data.finished) {
                              currentChunk = response.data.next_chunk;
                              uploadChunk(); // Process next chunk
                          } else {
                              // form.querySelector('input[name="recipient_group_id"]').value = groupId;
                              message.innerHTML = `<p style="color: green;">Upload complete!</p>`;
                              getUploadData(response.data.user, groupId);
                              showStep(currentStep + 1);
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

          // Start chunked upload
          uploadChunk();
      });
  }

}

});