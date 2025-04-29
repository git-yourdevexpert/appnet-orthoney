const multiStepForm = document.querySelector("#multiStepForm");
const customer_dashboard_recipient_list = document.querySelector("#customer-dashboard-recipient-list");

window.addEventListener('load', function() {
  setTimeout(() => {
          const loader = document.querySelector(".multiStepForm.loader"); // Find loader inside the form
          if (loader) {
              loader.style.display = "none"; // Hide the loader
          }
      
  }, 1500);
});

document.addEventListener("DOMContentLoaded", function () {

  if (multiStepForm || customer_dashboard_recipient_list) {
  document.querySelectorAll(".editProcessName").forEach((button) => {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      const process_name = event.target.getAttribute("data-name"); // Get process name
      let method = 'order-process';
      if(customer_dashboard_recipient_list){
         method = 'group';
      }
      Swal.fire({
        title: "Enter Recipient List Name",
        text: "The recipient list name for easy future reference.",
        input: "text",
        inputPlaceholder: "Enter Recipient List Name",
        inputAttributes: {
          autocapitalize: "off",
        },
        inputValue: process_name,
        showCancelButton: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false,
        confirmButtonText: "Save and Continue",
        showLoaderOnConfirm: true,
        reverseButtons: true,
        preConfirm: async (groupName) => {
          if (!groupName) {
            Swal.showValidationMessage("Group name is required!");
            return false; // Prevent proceeding if validation fails
          }

          process_group_popup();

          let mappId = getURLParam("pid");
          if(customer_dashboard_recipient_list){
            mappId = customer_dashboard_recipient_list.getAttribute('data-groupid');
          }

          return fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
              action: "edit_process_name",
              group_name: groupName,
              security: oam_ajax.nonce,
              method: method,
              pid: mappId,
            }),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                document.querySelectorAll(".editProcessName").forEach(editProcessName => {
                  editProcessName.setAttribute("data-name", groupName);
                  
                  // Get the closest parent <p> tag safely
                  let parentParagraph = editProcessName.closest(".group-name");
                  
                  if (parentParagraph) {
                    let strongTag = parentParagraph.querySelector("strong");
                    if (strongTag) {
                      strongTag.innerHTML = groupName;
                    }
                  }
                });

                Swal.fire({
                  title: data.data.message,
                  icon: "success",
                  timer: 2500,
                  showConfirmButton: false,
                  timerProgressBar: true,
                  allowOutsideClick: false,
                  allowEscapeKey: false,
                  allowEnterKey: false,
                });
              } else {
                throw new Error(data.data?.message || "Failed to update group name.");
              }
            })
            .catch((error) => {
              Swal.fire({
                title: "Error",
                text: error.message || "An error occurred while updating the group name.",
                icon: "error",
              });
            });
        },
      });
    });
  });
}

  if (multiStepForm) {

    // document.querySelectorAll("table tbody").forEach((table) => {
    //   const rows = table.querySelectorAll("tr");
    //   rows.forEach((row, index) => {
    //     if (index >= 10) {
    //       row.classList.add("hide");
    //     }
    //   });
    // });

    document.querySelectorAll("button.view-all-recipients").forEach((button) => {
        button.addEventListener("click", function (event) {
          event.preventDefault();

          let status = this.getAttribute("data-status");
          const table = this.closest("div").querySelector("table");

          if (table) {
            const rows = table.querySelectorAll("tr");
            const totalRows = rows.length - 1; // Excluding the header

            if (status == 0) {
              // Show all rows when clicked
              rows.forEach((row, index) => {
                if (index > 0) row.classList.remove("hide");
              });
              this.textContent = "Hide Recipients";
              this.setAttribute("data-status", 1);
            } else {
              // Hide rows beyond the first 10
              rows.forEach((row, index) => {
                if (index > 10) row.classList.add("hide");
              });
              this.textContent = "View All Recipients";
              this.setAttribute("data-status", 0);
            }
          }
        });
      });

    document.querySelectorAll(".scroll-section-btn").forEach((button) => {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        const sectionId = this.getAttribute("data-section");
        const targetSection = document.getElementById(sectionId);

        if (targetSection) {
          const offset = 100; // Adjust this value as needed
          const targetPosition =
            targetSection.getBoundingClientRect().top + window.scrollY - offset;

          smoothScroll(targetPosition, 1000);
        }
      });
    });
    
    function smoothScroll(target, duration) {
      const start = window.scrollY;
      const distance = target - start;
      let startTime = null;

      function animation(currentTime) {
        if (!startTime) startTime = currentTime;
        const timeElapsed = currentTime - startTime;
        const progress = Math.min(timeElapsed / duration, 1);

        window.scrollTo(0, start + distance * easeOutQuad(progress));

        if (timeElapsed < duration) {
          requestAnimationFrame(animation);
        }
      }

      function easeOutQuad(t) {
        return t * (2 - t); // Smooth deceleration
      }

      requestAnimationFrame(animation);
    }

    let currentStep = 0;
    const steps = document.querySelectorAll(".step");
    const stepNavItems = document.querySelectorAll(".step-nav-item");
    const pid = document.querySelector("#multiStepForm #pid");
    const singleAddress = document.querySelector(".single-address-order");
    const multipleAddress = document.querySelector(".multiple-address-order");

    let activeStepItem = document.querySelector(".step-nav-item.active");
    if (activeStepItem) {
      let stepIndex = parseInt(activeStepItem.getAttribute("data-step"), 10);
      if (!isNaN(stepIndex)) {
        showStep(stepIndex);
        currentStep = stepIndex;
      } else {
        showStep(currentStep);
      }
    }

   

    

    document
      .querySelectorAll("#multiStepForm .next-with-ortHoney-affiliates")
      .forEach((button) => {
        button.addEventListener("click", function (event) {
          event.preventDefault();

          // Find the selected value (assuming it comes from a radio or dropdown within the form)
          let selectedAffiliate = "Orthoney";

          // Update the #affiliate_select select box if a value is found
          if (selectedAffiliate) {
            let affiliateSelect = document.querySelector("#affiliate_select");
            if (affiliateSelect) {
              affiliateSelect.value = selectedAffiliate;
              affiliateSelect.dispatchEvent(new Event("change")); // Trigger change event if needed
            }
          }
          currentStep++;
          showStep(currentStep);
          processDataSaveAjax(pid?.value || "0", currentStep);
        });
      });

      document.querySelectorAll("#multiStepForm .next").forEach((button) => {
        button.addEventListener("click", function (event) {
        const address_block_error_message= document.querySelector('.address-block .error-message');
        address_block_error_message.style.display = 'none';
        event.preventDefault();
        
        console.log(currentStep);

        const uploadTypeOutput = document.querySelector(
          'input[name="upload_type_output"]:checked'
        );
        const deliveryPreference = document.querySelector(
          'input[name="delivery_preference"]:checked'
        );

        if (currentStep !== 0) {
          if (deliveryPreference) {
            console.log("1");
            // console.log(uploadTypeOutput.value);
            if (validateCurrentStep() && currentStep === 1) {
              if (deliveryPreference.value !== "single_address") {
                console.log("2");
                if (!uploadTypeOutput || uploadTypeOutput.value === '') {
                  
                  return;
                }
                process_group_popup();
                currentStep += uploadTypeOutput.value === "add-manually" || uploadTypeOutput.value === "select-group" ? 2 : 1;
                // currentStep = Math.min(currentStep, steps.length - 1);
                console.log("currentStep", currentStep);
                if (uploadTypeOutput.value === "add-manually") {
                  addRecipientManuallyPopup(1);
                }

                if (uploadTypeOutput.value === "select-group") {
                  console.log("select-group");
                  var selectedValues = Array.from(
                    document.querySelector('select[name="groups[]"]')
                      .selectedOptions
                  ).map((option) => option.value);

                  addRecipientSelectedGroupValues(selectedValues).then(
                    (status) => {
                      if (status === false) {
                        return;
                      } else {
                        // currentStep = currentStep + 1;
                        console.log("currentStep", currentStep);
                        setTimeout(() => {
                          // showStep(currentStep);
                          processDataSaveAjax(pid?.value || "0", currentStep);
                        }, 500);
                      }
                      return;
                    }
                  );
                }
              } else {
                console.log("3");
                process_group_popup();
                currentStep = Math.max(currentStep, steps.length - 1);
              }
              if (
                uploadTypeOutput &&
                uploadTypeOutput.value !== "" &&
                uploadTypeOutput.value !== "select-group" &&
                (uploadTypeOutput.value !== "add-manually" || deliveryPreference.value !== "single_address")
              ) {
                process_group_popup();
                console.log("33");
                if (uploadTypeOutput.value === "upload-csv") {
                  showStep(currentStep);
                }else{
                  process_group_popup();
                }
              }
              processDataSaveAjax(pid?.value || "0", currentStep);
            }
          } else {
            console.log("4");
            if (!deliveryPreference || deliveryPreference.value === '') {
              
              address_block_error_message.style.display = 'block';
            }else{
              if (currentStep !== 1) {
                process_group_popup();
                currentStep++;
                showStep(currentStep);
                processDataSaveAjax(pid?.value || "0", currentStep);
              }
          }
          }
        } else {
          console.log("5");
          currentStep++;
          if(currentStep == 1){
            const affiliateSelect = document.querySelector('#affiliate_select');
            const selectedOption = affiliateSelect.options[affiliateSelect.selectedIndex];
            const dataToken = selectedOption.getAttribute('data-token');
            
            if (dataToken) {
              console.log(dataToken);
              document.cookie = `yith_wcaf_referral_token=${dataToken}; path=/; max-age=${1 * 86400}`;
              document.cookie = `yith_wcaf_referral_history=${dataToken}; path=/; max-age=${1 * 86400}`;
            }
          
          }
          process_group_popup();
          showStep(currentStep);
          processDataSaveAjax(pid?.value || "0", currentStep);
        }
      });
    });


    document.querySelectorAll('input[name="delivery_preference"]').forEach(radio => {
      radio.addEventListener("click", function () {
        const address_block_error_message= document.querySelector('.address-block .error-message');
        const singleAddress= document.querySelector('.address-inner .single-address-order');
        const multipleAddress= document.querySelector('.address-inner .multiple-address-order');
        if (!singleAddress || !multipleAddress) return;
        
        address_block_error_message.style.display = 'none';
        const step = this.closest(".step");
        const nextButton = step?.querySelector("button.next");
        const singleInput = singleAddress.querySelector('input[name="single_address_quantity"]');
        const textarea = singleAddress.querySelector('input[name="single_address_greeting"]');
        const multipleInput = multipleAddress.querySelector('input[name="multiple_address_output"]');
    
        const toggleField = (field, required) => {
          if (!field) return;
          field.style.border = "";
          field.toggleAttribute("required", required);
          const errorMessage = field.nextElementSibling;
          if (errorMessage?.classList.contains("error-message")) {
            errorMessage.innerHTML = "";
          }
        };
    
        const isSingle = this.value === "single_address";
        singleAddress.style.display = isSingle ? "grid" : "none";
        multipleAddress.style.display = isSingle ? "none" : "grid";
    
        if (isSingle) {
          nextButton?.setAttribute("value", "single_address");
        } else {
          nextButton?.removeAttribute("value");
        }
    
        toggleField(singleInput, isSingle);
        toggleField(textarea, isSingle);
        toggleField(multipleInput, !isSingle);
      });
    });

    document
    .querySelectorAll('#multiStepForm input[name="upload_type_output"]')
    .forEach((input) => {
      input.addEventListener("click", function (event) {
        // event.preventDefault();
        const groups_wrapper = document.querySelector(
          ".multiple-address-order .groups-wrapper"
        );
        const multiple_address_output = document.querySelector(
          ".multiple-address-order #multiple-address-output"
        );
        if(groups_wrapper){
          const groups_select = groups_wrapper.querySelector("select");
        }
        multiple_address_output.value = this.value;
        if (this.value == "select-group") {
          groups_wrapper.style.display = "block";
          if(groups_wrapper){
            groups_select.setAttribute("required", "required");
          }
        } else {
          groups_wrapper.style.display = "none";
          if(groups_wrapper){
            groups_select.removeAttribute("required");
          }
        }
      });
    });

      document.querySelectorAll("#checkout_proceed_with_addresses_button").forEach((button) => {
        button.addEventListener("click", async function (event) {
           event.preventDefault();
          const unverifiedButton = document.querySelector("#checkout_proceed_with_only_unverified_addresses");
          const verifiedButton = document.querySelector("#checkout_proceed_with_only_verified_addresses");
    
          const unverified_table = document.querySelector("#unverified-block"); // Select a single element
          const verified_table = document.querySelector("#verified-block"); // Select a single element
    
          let html = ``;
          if (!unverifiedButton && verifiedButton) {
            html = `All recipients have been successfully validated!`;
          }
          if (unverifiedButton && !verifiedButton) {
            html = `All recipients have been unsuccessfully validated!`;
          }
    
         
          if (unverifiedButton && verifiedButton) {
            const unverifiedCount = unverified_table.getAttribute("data-count");
            const verifiedCount = verified_table.getAttribute("data-count");
           html = `Out of the ${(parseInt(verifiedCount) + parseInt(unverifiedCount))} recipients, ${(parseInt(verifiedCount))}  have been successfully verified.`;

            html += `<div class='exceptions'><strong>Exceptions: </strong><ul>`;
            
            if (verified_table) {
              if (verifiedCount != 0) {
                html += `<li><span>Verified Recipients: </span> ${verifiedCount}</li>`;
              }
            }
            if (unverified_table) {
              if (unverifiedCount != 0) {
                html += `<li><span>Unverified Recipients: </span> ${unverifiedCount}</li>`;
              }
            }
            
            html += `<li class="total-recipients"><span>Total Recipients: </span> ${(parseInt(verifiedCount) + parseInt(unverifiedCount))}</li></ul>`;
          }
          
          
          const result = await Swal.fire({
            title:  html,
            // html: html,
            //icon: "question",
            showCancelButton: true,
            showConfirmButton: false,
            showDenyButton: false,
            confirmButtonColor: "#3085d6",
            denyButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Proceed With All Records",
            cancelButtonText: "No, I Want to Update/Fix Records",
            denyButtonText: "Proceed With Only Verified Records",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: true,
            width: "970px",
            willOpen: () => {
              if (unverifiedButton) {  
                Swal.getConfirmButton().style.display = "inline-block";
          
                if (unverifiedButton && !verifiedButton) {
                  Swal.getConfirmButton().textContent = "Yes, Proceed"; 
                }
              }
          
              if (verifiedButton) {  
                Swal.getDenyButton().style.display = "inline-block";
                
                if (!unverifiedButton && verifiedButton) {
                  Swal.getConfirmButton().textContent = "Yes, Proceed"; 
                }
              }
            },
          });

          if (result.isConfirmed) {
            process_group_popup();
              event.target.closest("div").querySelector('input[name="checkout_proceed_with_multi_addresses_status"]').value = "only_verified";
              const form = document.querySelector("#multiStepForm");
              await process_to_checkout_ajax_part(form, 0);
            }
          if (result.isDenied) {
            process_group_popup();
            event.target.closest("div").querySelector('input[name="checkout_proceed_with_multi_addresses_status"]').value = "unverified";
            const form = document.querySelector("#multiStepForm");
            await process_to_checkout_ajax_part(form, 1);
          }
        });
      });


    // document
    //   .querySelectorAll("#checkout_proceed_with_only_verified_addresses")
    //   .forEach((button) => {
    //     button.addEventListener("click", async function (event) {
    //       event.preventDefault();
    //       const form = document.querySelector("#multiStepForm");
    //       event.target
    //         .closest("div")
    //         .querySelector(
    //           'input[name="checkout_proceed_with_multi_addresses_status"]'
    //         ).value = "only_verified";
    //       let html =
    //         "<p>Are you sure you want to proceed with only verified addresses?</p>";
    //       const processCheckoutStatus = document.querySelector(
    //         'input[name="processCheckoutStatus"]'
    //       );
    //       const delivery_preference = document.querySelector(
    //         'input[name="delivery_preference"]:checked'
    //       );
    //       const checkout_proceed_with_multi_addresses_status =
    //         document.querySelector(
    //           'input[name="checkout_proceed_with_multi_addresses_status"]'
    //         );

    //       if (processCheckoutStatus) {
    //         if (
    //           processCheckoutStatus.value == 5 &&
    //           delivery_preference.value == "multiple_address" &&
    //           checkout_proceed_with_multi_addresses_status.value ==
    //             "only_verified"
    //         ) {
    //           await process_to_checkout_ajax_part(form, 1);
    //         } else {
    //           await process_to_checkout(form, html, 1);
    //         }
    //       }
    //     });
    //   });

    // document.querySelectorAll("#checkout_proceed_with_only_unverified_addresses").forEach((button) => {
    //     button.addEventListener("click", async function (event) {
    //       event.preventDefault();
    //       const form = document.querySelector("#multiStepForm");
    //       event.target
    //         .closest("div")
    //         .querySelector(
    //           'input[name="checkout_proceed_with_multi_addresses_status"]'
    //         ).value = "unverified";
    //       let html =
    //         "<p>Are you sure you want to proceed with unverified addresses?</p>";

    //       const processCheckoutStatus = document.querySelector(
    //         'input[name="processCheckoutStatus"]'
    //       );
    //       const delivery_preference = document.querySelector(
    //         'input[name="delivery_preference"]:checked'
    //       );
    //       const checkout_proceed_with_multi_addresses_status =
    //         document.querySelector(
    //           'input[name="checkout_proceed_with_multi_addresses_status"]'
    //         );

    //       if (processCheckoutStatus) {
    //         if (
    //           processCheckoutStatus.value == 5 &&
    //           delivery_preference.value == "multiple_address" &&
    //           checkout_proceed_with_multi_addresses_status.value ==
    //             "only_verified"
    //         ) {
    //           await process_to_checkout_ajax_part(form, 0);
    //         } else {
    //           await process_to_checkout(form, html, 0);
    //         }
    //       }
    //     });
    //   });

    // async function process_to_checkout(form, html, status) {
    //   try {
    //     const result = await Swal.fire({
    //       title: "",
    //       html: html,
    //       //icon: "question",
    //       showCancelButton: true,
    //       confirmButtonColor: "#3085d6",
    //       cancelButtonColor: "#d33",
    //       confirmButtonText: "Yes, Proceed",
    //       cancelButtonText: "No, I Want to Add/Edit Records.",
    //       allowOutsideClick: false,
    //       allowEscapeKey: false,
    //       allowEnterKey: false,
    //       reverseButtons: true,
    //       width: "650px",
    //     });

    //     if (!result.isConfirmed) return; // Exit if user cancels

    //     if (typeof process_group_popup === "function") {
    //       process_group_popup(); // Ensure the function exists
    //     }

    //     if (!form) {
    //       Swal.fire("Error", "Form not found!", "error");
    //       return;
    //     }

    //     await process_to_checkout_ajax_part(form, status);
    //   } catch (error) {
    //     console.error("AJAX error:", error);
    //     Swal.fire(
    //       "Error",
    //       `Request failed: ${error.message || "Unknown error"}`,
    //       "error"
    //     );
    //   }
    // }

    async function process_to_checkout_ajax_part(form, status) {
      try {
        const formData = new FormData(form);
    
        formData.append("action", "orthoney_process_to_checkout_ajax");
        formData.append("currentStep", typeof currentStep !== "undefined" ? currentStep : "");
        formData.append("security", oam_ajax.nonce);
        formData.append("status", status);
    
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
          icon: "success",
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

    document
      .querySelectorAll(".keep_this_and_delete_others")
      .forEach((button) => {
        button.addEventListener("click", function (event) {
          event.preventDefault();

          // Find the closest <tr> to the clicked button
          let clickedRow = this.closest("tr");
          let clickedRowId = clickedRow.getAttribute("data-id");
          let totalCount = 0;
          let recipientname = this.getAttribute("data-recipientname");

          // Find the group header above the clicked row
          let groupHeader = clickedRow.previousElementSibling;
          while (
            groupHeader &&
            !groupHeader.classList.contains("group-header")
          ) {
            groupHeader = groupHeader.previousElementSibling;
          }

          if (groupHeader) {
            totalCount = groupHeader.getAttribute("data-count");
            let groupId = groupHeader.getAttribute("data-group");

            // Collect all data-id values in the group
            let allIds = [];
            let currentRow = groupHeader.nextElementSibling;

            while (
              currentRow &&
              !currentRow.classList.contains("group-header")
            ) {
              if (currentRow.hasAttribute("data-id")) {
                allIds.push(currentRow.getAttribute("data-id"));
              }
              currentRow = currentRow.nextElementSibling;
            }

            // Remove the clicked row's ID from the array (to keep it)
            let filteredIds = allIds.filter((id) => id !== clickedRowId);

            Swal.fire({
              title: "Are you sure?",
              text: `Total ${totalCount} records found for ${recipientname}. Keep this record and delete the other ${totalCount - 1}`,
              icon: "question",
              showCancelButton: true,
              confirmButtonColor: "#3085d6",
              cancelButtonColor: "#d33",
              confirmButtonText: "Yes",
              cancelButtonText: "Cancel",
              allowOutsideClick: true,
            }).then((result) => {
              if (result.isConfirmed) {
                process_group_popup();
                fetch(oam_ajax.ajax_url, {
                  method: "POST",
                  headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                  },
                  body: new URLSearchParams({
                    action: "keep_this_and_delete_others_recipient",
                    delete_ids: filteredIds,
                    security: oam_ajax.nonce,
                  }),
                })
                  .then((response) => response.json())
                  .then((data) => {
                    if (data.success) {
                      Swal.fire({
                        title: "Recipient marge successfully!",
                        icon: "success",
                        showConfirmButton: false,
                        timerProgressBar: false,
                      });

                      setTimeout(function () {
                        window.location.reload();
                      }, 1500);
                    } else {
                      Swal.fire({
                        title: "Error",
                        text: data.data.message || "Failed to marge recipient.",
                        icon: "error",
                      });
                    }
                  })
                  .catch(() => {
                    Swal.fire({
                      title: "Error",
                      text: "An error occurred while margining the recipient.",
                      icon: "error",
                    });
                  });
              }
            });
          }
        });
      });

      

    

    document.querySelectorAll("#multiStepForm .back").forEach((button) => {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        currentStep = Math.max(0, currentStep - 1);
        showStep(currentStep);
        processStepSaveAjax(pid?.value || "0", currentStep);
      });
    });

    document.querySelectorAll(".save-data").forEach((button) => {
      button.addEventListener("click", function (event) {
        event.preventDefault();
        collectFormData();
      });
    });

    document
      .querySelectorAll(".verifyRecipientAddressButton")
      .forEach((button) => {
        button.addEventListener("click", async function (event) {
          // Add 'async' here for await to work
          event.preventDefault();

          const totalCount = event.target.getAttribute("data-totalcount");
          const successCount = event.target.getAttribute("data-successcount");
          const newCount = event.target.getAttribute("data-newcount");
          const failCount = event.target.getAttribute("data-failcount");
          const alreadyOrderCount = event.target.getAttribute("data-alreadyOrderCount");
          const duplicateCount = event.target.getAttribute("data-duplicatecount");
          let html = ``;

          if (parseInt(successCount) + parseInt(newCount) == totalCount && failCount == 0) {
              html = `All recipients have been successfully validated!`;
          } else if (parseInt(successCount) + parseInt(newCount) != totalCount && failCount != 0 && successCount == 0 && newCount == 0 && duplicateCount == 0) {
            html = `All recipients have been failed!`;
          }else if (parseInt(successCount) + parseInt(newCount) != totalCount && failCount != 0 && successCount == 0 && newCount == 0 && duplicateCount != 0) {
            html = `Out of ${totalCount} recipients, ${parseInt(duplicateCount)} have been duplicated!`;
          }else if (parseInt(successCount) + parseInt(newCount) != totalCount && failCount == 0 && successCount == 0 && newCount == 0 && duplicateCount != 0) {
            html = `All recipients have been duplicated!`;
          }

          if(html == ''){
            html += `Out of ${totalCount} recipients, `;
          
            if (successCount != 0  || newCount != 0) {
              html += `${parseInt(successCount) + parseInt(newCount)} have been successfully validated. `;
            }
        }
          
          // if (parseInt(alreadyOrderCount) !== 0) {
          //   html += `<div><small>${alreadyOrderCount} recipients have already received the Jar this year.</small></div>`;
          // }

          if (failCount != 0 || duplicateCount != 0 || newCount != 0 || successCount != 0) {
            html += "<div class='exceptions'><strong>Exceptions: </strong><ul>";
          }

         

          if (successCount != 0) {
            html += `<li><span>Successful Recipients: </span> ${successCount}</li>`;
          }
          if (failCount != 0) {
            html += `<li><span>Failed Recipients: </span> ${failCount}</li>`;
          }
         
          if (parseInt(newCount) !== 0) {
            html += `<li><span>New Recipient: </span> ${newCount}</li>`;
          }
          if (duplicateCount != 0) {
            html += `<li><span>Duplicate Recipients: </span> ${duplicateCount}</li>`;
          } 
          html += `<li class="total-recipients"><span>Total Recipients: </span> ${totalCount}</li>`;

          html += `</ul></div>`;
        
          // html += `<p>Please confirm if you would like to proceed with the successfully added records.</p>`;

          const result = await Swal.fire({
            title: html,
            // html: html,
            //icon: "question",
            showCancelButton: true,
            showConfirmButton: false,
            showDenyButton: false,
            confirmButtonColor: "#3085d6",
            denyButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Proceed With Duplicate Records",
            cancelButtonText: "No, I Want to Update/Fix Records",
            denyButtonText: "Proceed Without Duplicate Records",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: true,
            width: "970px",
            willOpen: () => {
              if (duplicateCount > 0) {
                Swal.getConfirmButton().style.display = "inline-block";
                if (successCount == 0) {
                  // Swal.getConfirmButton().textContent = "Yes, Proceed"; 
                }
              }
              if (successCount > 0 || newCount != 0) {
                Swal.getDenyButton().style.display = "inline-block";
                if (duplicateCount == 0) {
                  Swal.getDenyButton().textContent = "Yes, Proceed"; 
                }
              }
            },
          });

  

          if (result.isConfirmed || result.isDenied) {
            process_group_popup();

            const form = document.querySelector("#multiStepForm");
            const formData = new FormData(form);
            formData.append(
              "action",
              "orthoney_order_step_process_completed_ajax"
            );
            formData.append(
              "currentStep",
              typeof currentStep !== "undefined" ? currentStep : ""
            );

            if(result.isDenied){
              formData.append("duplicate", 0);
            }else{
              formData.append("duplicate", 1);
            }
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
                Swal.fire(
                  "Error",
                  responseData.message || "An error occurred",
                  "error"
                );
                return;
              }

              setTimeout(function () {
                window.location.reload();
              }, 1000);
            } catch (error) {
              console.error("AJAX error:", error);
              Swal.fire(
                "Error",
                `Request failed: ${error.message || "Unknown error"}`,
                "error"
              );
            }
          }
          
        });
      });

    document
      .querySelectorAll("#multiStepForm .reverifyAddress")
      .forEach((reverifyAddress) => {
        reverifyAddress.addEventListener("click", function (event) {
          event.preventDefault();
          process_group_popup(
            "Please wait, the address verification is in progress."
          );

          const target = event.target;
          const recipientTr = target.closest("tr");
          const recipientID = recipientTr?.getAttribute("data-id");

          fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
              action: "reverify_address_recipient",
              id: recipientID,
              security: oam_ajax.nonce,
            }),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                Swal.fire({
                  title: data.data.message,
                  icon: "success",
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
                  title: "Error",
                  text: data.data?.message || "Failed to remove recipient.",
                  icon: "error",
                });
              }
            })
            .catch((error) => {
              Swal.fire({
                title: "Error",
                text: "An error occurred while removing the recipient.",
                icon: "error",
              });
            });
        });
      });

    const nextButton = document.getElementById("nextButton");

    if (nextButton) {
      nextButton.addEventListener("click", function (e) {
        e.preventDefault();
        const form = document.querySelector("#multiStepForm");
        csv_upload(form);
      });
    }

 
    
    document.addEventListener("click", function (event) {
      if (event.target.classList.contains("submit_csv_file")) {
        event.preventDefault();
        console.log("CSV 1");
        if (validateCurrentStep()) {
          console.log("CSV 2");
          showStep(currentStep);

          processDataSaveAjax(pid?.value || "0", currentStep);

          const form = document.querySelector("#multiStepForm");
          csv_upload(form);
        }
      }
    });

    function addRecipientSelectedGroupValues(selectedValues) {
      process_group_popup(
        "Please wait while Recipients are being processed..."
      );

      return fetch(oam_ajax.ajax_url, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          action: "save_group_recipient_to_order_process",
          groups: selectedValues,
          security: oam_ajax.nonce,
          pid: getURLParam("pid"),
        }),
      })
        .then((response) => response.json()) // Convert response to JSON
        .then((data) => {
          if (data.success) {
            return true;
          } else {
            Swal.fire({
              title: "Error",
              text: "Failed to remove recipient.",
              icon: "error",
            });
            return false;
          }
        })
        .catch((error) => {
          console.error("Fetch error:", error);
          Swal.fire({
            title: "Error",
            text: "An error occurred while processing recipients.",
            icon: "error",
          });
          return false;
        });
    }

    function showStep(index) {
      steps.forEach(
        (step, i) => (step.style.display = i === index ? "block" : "none")
      );
      stepNavItems.forEach((navItem, i) => {
        navItem.classList.toggle("active", i === index);
      });
    }

    function csv_upload(form) {
      if (form) {
        const file = form.querySelector('input[type="file"]').files[0];
        if (!file) {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: "Please select a file to upload!",
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
        let pid = getURLParam("pid");

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

            xhr.onload = function () {
              if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                  if (currentChunk === 0) {
                    totalRows = response.data.total_rows;
                    pid = response.data.pid;

                    // Show progress bar popup only after a successful start
                    Swal.fire({
                      title: "Uploading Recipient Data",
                      text: "Please wait, the recipient data upload is in progress.",
                      html: `
                        <p>Please wait, the recipient data upload is in progress.</p><div style="width: 100%; background-color: #ccc; border-radius: 5px; overflow: hidden;">
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
                      title: "Upload Complete!",
                      showConfirmButton: false,
                      timer: 4500,
                    });
                    setTimeout(() => {
                      window.location.reload();
                    }, 1000);
                  }
                } else {
                  Swal.fire({
                    icon: "error",
                    title: "Upload Failed",
                    text: response.data.message,
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
                text: "A network error occurred during upload.",
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
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          action: "orthoney_order_step_process_ajax",
          process_value: process_value,
          currentStep: currentStep,
          security: oam_ajax.nonce,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            pid.value = data.data.pid;

            console.log(data.data.pid);
            const basePath = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set("pid", data.data.pid);

            const newUrl = `${basePath}?${urlParams.toString()}`;
            history.pushState(null, "", newUrl);
          }
          if (data.success) pid.value = data.data.pid;
        })
        .catch((error) => console.error("Fetch error:", error));
    }

    function checkAddressMissingField() {
      let requiredFields = {
        single_order_address_1: "Address Line 1",
        single_order_city: "City",
        single_order_state: "State",
        single_order_zipcode: "Zip Code",
      };

      let missingFields = [];

      for (let field in requiredFields) {
        let input = document.getElementById(field);
        let errorMessageElement = input
          ? input.parentElement.querySelector(".error-message")
          : null;

        if (!input || input.value.trim() === "") {
          missingFields.push(requiredFields[field]);

          if (errorMessageElement) {
            errorMessageElement.textContent =
              input.dataset.errorMessage ||
              `Please enter ${requiredFields[field]}.`;
            errorMessageElement.style.color = "red";
          }
        } else {
          // ZIP code validation for the US
          if (field === "single_order_zipcode") {
            let zipCode = input.value.trim();
            let usZipRegex = /^\d{5}(-\d{4})?$/; // Matches 5-digit ZIP or ZIP+4 (e.g., 12345 or 12345-6789)
            if (!usZipRegex.test(zipCode)) {
              missingFields.push("Valid Zip Code");
              if (errorMessageElement) {
                errorMessageElement.textContent =
                  "Please enter a valid US ZIP code (e.g., 12345 or 12345-6789).";
                errorMessageElement.style.color = "red";
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

    document.addEventListener("click", function (event) {
      if (event.target.id === "singleAddressCheckout") {
        event.preventDefault(); // Only prevent default for this specific button
        processStepSaveAjax(pid?.value || "0", 5);
        
        let result = checkAddressMissingField();
        
        if (result === true) {
          process_group_popup("Please wait while we verify the address");
          

          const formData = collectFormData();
          formData.append("action", "orthoney_single_address_data_save_ajax");
          formData.append("security", oam_ajax.nonce);

          fetch(oam_ajax.ajax_url, {
            method: "POST",
            body: formData,
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                console.log('success');
                singleAddressDataSaveAjax(1);
              } else {
                Swal.fire({
                  title: "Address Not Verified!",
                  text: 'The shipping address you entered could not be verified. Unverified addresses may result in delivery issues or delays. Would you like to proceed with this address?',
                  showCancelButton: true,
                  showConfirmButton: true,
                  confirmButtonColor: "#3085d6",
                  cancelButtonColor: "#d33",
                  confirmButtonText: "Yes, proceed",
                  cancelButtonText: "No, I don't want to",
                  
                  allowOutsideClick: false,
                  allowEscapeKey: false,
                  allowEnterKey: false,
                  reverseButtons: true,
                }).then((result) => {
                  if (result.isConfirmed) {
                    singleAddressDataSaveAjax(1);
                  }
                });
              }
            })
            .catch((error) => console.error("Fetch error:", error));
        }
      }
    });

   
    function singleAddressDataSaveAjax($status = 0) {
      process_group_popup("Please wait while we prepare your order.");

        const formData = collectFormData();
        formData.append("action", "orthoney_single_address_data_save_ajax");
        formData.append("security", oam_ajax.nonce);
        formData.append("status", $status);
        console.log( $status);

        fetch(oam_ajax.ajax_url, {
          method: "POST",
          body: formData,
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // let message = data.data.message;
              
              const processCheckoutStatus = document.querySelector(
                'input[name="processCheckoutStatus"]'
              );
              const delivery_preference = document.querySelector(
                'input[name="delivery_preference"]'
              );

              if (
                processCheckoutStatus &&
                processCheckoutStatus.value == 5 &&
                delivery_preference.value == "single_address"
              ) {
                // message = "Order is preparing.";
                // if(message != ''){
              //   Swal.fire({
              //     title: message,
              //     icon: "success",
              //     timer: 2500,
              //     showConfirmButton: false,
              //     timerProgressBar: true,
              //   });
              // }
              }

              

              // Redirect to checkout after a delay (uncomment if needed)
              setTimeout(() => {
                window.location.href = data.data.checkout_url;
              }, 1500);

            } else {
              Swal.fire({
                title: "Error",
                text: data.data.message || "Error fetching address validation.",
                icon: "error",
              });
              
            }
          })
          .catch((error) => console.error("Fetch error:", error));
      
    }

    function processDataSaveAjax(pid, currentStep) {
      const formData = collectFormData();
      formData.append("action", "orthoney_order_process_ajax");
      if (pid == "") {
        pid = getURLParam("pid");
      }
      formData.append("pid", pid);
      formData.append("currentStep", currentStep);
      formData.append("security", oam_ajax.nonce);

      fetch(oam_ajax.ajax_url, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            const pid = document.querySelector("#multiStepForm #pid");
            pid.value = data.data.pid;

            const basePath = window.location.pathname;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set("pid", data.data.pid);

            const newUrl = `${basePath}?${urlParams.toString()}`;
            history.pushState(null, "", newUrl);
            console.log(data.data.step);
            if (data.data.step == 4 || data.data.groups == 1) {
              setTimeout(function () {
                window.location.reload();
              }, 1000);
            }else{
              Swal.close();
            }
          }
        })
        .catch((error) => console.error("Fetch error:", error));
    }

    function collectFormData() {
      const form = document.querySelector("#multiStepForm");
      const formData = new FormData(form);
      return formData;
    }

    function validateCurrentStep() {
      const currentInputs = steps[currentStep].querySelectorAll("[required]");
      let isValid = true;
      currentInputs.forEach((input) => {
        const errorMessage =
          input.parentElement.querySelector(".error-message");
        if (!input.value) {
          input.style.border = "1px solid red";
          if (errorMessage)
            errorMessage.textContent =
              input.getAttribute("data-error-message") ||
              "This field is required.";
          isValid = false;
        } else {
          input.style.border = "";
          if (errorMessage) errorMessage.textContent = "";
        }
      });
      return isValid;
    }

    let result = checkAddressMissingField();

    if (result === true) {
      const processCheckoutStatus = document.querySelector(
        'input[name="processCheckoutStatus"]'
      );
      const delivery_preference = document.querySelector(
        'input[name="delivery_preference"]'
      );

      if (processCheckoutStatus) {
        if (
          processCheckoutStatus.value == 5 &&
          delivery_preference.value == "single_address"
        ) {
          let button = document.querySelector(
            "#multiStepForm button#singleAddressCheckout"
          );
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
        .then((response) => response.blob())
        .then((blob) => {
          const fileName = fileUrl.split("/").pop(); // Extract filename from URL
          const file = new File([blob], fileName, { type: "text/csv" });

          // Create a DataTransfer object to set file input value
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(file);
          fileInput.files = dataTransfer.files;

          // console.log("File re-uploaded:", file.name);
        })
        .catch((error) => console.error("Error fetching the file:", error));
    }
  }
});

function addRecipientManuallyPopup(reload) {
  const processCheckoutStatus = document.querySelector(
    'input[name="processCheckoutStatus"]'
  );
  const delivery_preference = document.querySelector(
    'input[name="delivery_preference"]:checked'
  );
  const checkout_proceed_with_multi_addresses_status = document.querySelector(
    'input[name="checkout_proceed_with_multi_addresses_status"]'
  );

  // if (processCheckoutStatus && checkout_proceed_with_multi_addresses_status) {
  //   if (
  //     processCheckoutStatus.value == 5 &&
  //     delivery_preference.value == "multiple_address" &&
  //     checkout_proceed_with_multi_addresses_status.value == "only_verified"
  //   ) {
  //     let button = document.querySelector(
  //       "#multiStepForm button#checkout_proceed_with_only_verified_addresses"
  //     );
  //     if (button) {
  //       button.click();
  //     }
  //   }
  // }

  if (reload == 1) {
    setTimeout(function () {
      window.location.reload();
    }, 500);
  }
  console.log("check 1");
  let emptyDivs = [];

  const step_nav = document.querySelector(".step-nav-item.active");
  if (step_nav.getAttribute("data-step") == 3) {
    ["failCSVData", "successCSVData", "duplicateCSVData", "newCSVData"].forEach(
      (id) => {
        let div = document.getElementById(id);
        if (div && div.innerHTML.trim() !== "") {
          emptyDivs.push(id);
        }
      }
    );

    console.log('emptyDivs'+emptyDivs.length);
    const upload_type_output = document.querySelector(
      'input[name="upload_type_output"]:checked'
    );
    if (emptyDivs.length == 0 && (upload_type_output.value == "add-manually" || getURLParam('failed-recipients') != 'true')) {
      let button = document.querySelector(
        "#multiStepForm button.editRecipient"
      );
      setTimeout(() => {
        Swal.close();
      }, 1500);
      if (button) {
        setTimeout(() => {
          button.click();
        }, 1500);
      }
    }
    
    // duplicateCSVData
    // jQuery(document).ready(function ($) {
    //   const tableEl = $('#viewAllRecipientsPopupCheckout table');
    
    //   // Remove any rows with .group-header to avoid column mismatch errors
    //   tableEl.find('tbody tr.group-header').remove();
    
    //   // Initialize DataTable
    //   tableEl.DataTable({
    //     paging: true,
    //     info: true,
    //     searching: true,
    //     responsive: true,
    //     deferRender: false,
    //     lengthChange: false,
    //     // Optional: Improve visuals or behavior
    //     columnDefs: [
    //       { targets: '_all', className: 'dt-center' }
    //     ]
    //   });
    // });

    jQuery(document).ready(function ($) {
      const tableEl = $('#duplicateCSVData table');
      const tbody = tableEl.find('tbody');
    
      // Save group header rows and remove from DOM before DataTable init
      const groupHeaderRows = tbody.find('tr.group-header').detach();
    
      // Initialize DataTable
      const dataTable = tableEl.DataTable({
        paging: false,
        info: true,
        searching: true,
        responsive: true,
        deferRender: false,
        lengthChange: false
      });
    
      // Reinsert group-header rows and manage pagination on every redraw
      dataTable.on('draw', function () {
        // Remove previous group-header rows
        tbody.find('tr.group-header').remove();
    
        // Track which groups were added
        const visibleRows = dataTable.rows({ search: 'applied' }).nodes();
        const addedGroups = {};
    
        // Reinsert headers for visible groups
        $(visibleRows).each(function () {
          const groupId = $(this).data('group');
          if (groupId && !addedGroups[groupId]) {
            const headerRow = groupHeaderRows.filter(`[data-group="${groupId}"]`);
            if (headerRow.length) {
              $(this).before(headerRow.clone());
              addedGroups[groupId] = true;
            }
          }
        });
    
        // Hide pagination if only one page
        const pageInfo = dataTable.page.info();
        const wrapper = $(tableEl).closest('.dataTables_wrapper');
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
    
      // Trigger initial draw to reinsert group-headers
      dataTable.draw();
    });
    
    
    

    VerifyRecipientsDatatable();
    
    
    
    
  }
  if (step_nav.getAttribute("data-step") == 4) {
    jQuery(document).ready(function ($) {
    ["verifyRecord", "unverifiedRecord"].forEach((id) => {
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
    });
  }
}
