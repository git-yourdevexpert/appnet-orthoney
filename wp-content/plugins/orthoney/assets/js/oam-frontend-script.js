function setCookie(name, value, days) {
  document.cookie = `${name}=${value}; path=/; max-age=${
    days * 86400
  }; secure; samesite=strict`;
}

jQuery(document).ready(function ($) {
  if ($("#profile_state").length) {
    // Step 1: Modify option text
    $("#profile_state option").each(function () {
      var val = $(this).val();
      var text = $(this).text();

      if (val && text.indexOf("[" + val + "]") === -1) {
        $(this).text(text + " [" + val + "]");
      }
    });

    // Step 2: Initialize Select2
    $("#profile_state").select2();
  }
});

function affiliateDatatable() {
  ["affiliate-results"].forEach((id) => {
    const div = document.getElementById(id);
    if (div && div.innerHTML.trim() !== "") {
      const tableEl = div.querySelector("table");
      if (tableEl && !jQuery(tableEl).hasClass("dataTable")) {
        const $table = jQuery(tableEl);

        // Initialize DataTable
        const dataTable = $table.DataTable({
          paging: false,
          pageLength: 50,
          lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
          ],

          paging: true,
          fixedHeader: true,
          scrollCollapse: false,
          info: true,
          searching: true,
          responsive: true,
          deferRender: false,
          lengthChange: false,
          language: {
            search: "",
            searchPlaceholder: "Search..."
          },
          columnDefs: [
            {
              targets: -1, // -1 means "last column"
              orderable: false // disables sorting
            }
          ]
        });

        // Hide pagination & info if only 1 page
        dataTable.on("draw", function () {
          const pageInfo = dataTable.page.info();
          const wrapper = $table.closest(".dataTables_wrapper");
          const pagination = wrapper.find(".dataTables_paginate");
          const infoText = wrapper.find(".dataTables_info");

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

  ["affiliate-organizations-results"].forEach((id) => {
    const div = document.getElementById(id);
    if (div && div.innerHTML.trim() !== "") {
      const tableEl = div.querySelector("table");
      if (tableEl && !jQuery(tableEl).hasClass("dataTable")) {
        const $table = jQuery(tableEl);

        // Initialize DataTable
        const dataTable = $table.DataTable({
          paging: false,
          pageLength: 50,
          lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
          ],

          paging: true,
          fixedHeader: true,
          scrollCollapse: false,
          info: true,
          searching: true,
          responsive: true,
          deferRender: false,
          lengthChange: false,
          language: {
            emptyTable: "No Organizations found!",
            search: "",
            searchPlaceholder: "Search..."
          },
          columnDefs: [
            {
              targets: -1, // -1 means "last column"
              orderable: false // disables sorting
            }
          ]
        });

        // Hide pagination & info if only 1 page
        dataTable.on("draw", function () {
          const pageInfo = dataTable.page.info();
          const wrapper = $table.closest(".dataTables_wrapper");
          const pagination = wrapper.find(".dataTables_paginate");
          const infoText = wrapper.find(".dataTables_info");

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

  
["affiliate-orderlist-table"].forEach((id) => {
  const div = document.getElementById(id);
  if (div && div.innerHTML.trim() !== "") {
    const tableEl = div.querySelector("table");
    if (tableEl && !jQuery(tableEl).hasClass("dataTable")) {
      const $table = jQuery(tableEl);
      const dateColIndex = 5; // Change if Date column index is different
      const lastColIndex = $table.find("thead th").length - 1;

      // Custom sort: ignore '#' in the first column
      jQuery.fn.dataTable.ext.type.order["ignore-hash-asc"] = function (a, b) {
        return (
          (parseInt(a.replace(/^#/, ""), 10) || 0) -
          (parseInt(b.replace(/^#/, ""), 10) || 0)
        );
      };
      jQuery.fn.dataTable.ext.type.order["ignore-hash-desc"] = function (a, b) {
        return (
          (parseInt(b.replace(/^#/, ""), 10) || 0) -
          (parseInt(a.replace(/^#/, ""), 10) || 0)
        );
      };

      // Create Year filter dropdown with spacing
      const yearSelect = jQuery(`
        <select id="yearFilter" class="form-control" style="margin-left: 10px;">
          <option value="">Filter by Year</option>
        </select>
      `);

      // Initialize DataTable
      const dataTable = $table.DataTable({
        paging: true,
        pageLength: 50,
        lengthMenu: [
          [10, 25, 50, 100],
          [10, 25, 50, 100]
        ],
        fixedHeader: true,
        scrollCollapse: false,
        info: true,
        searching: true,
        responsive: true,
        deferRender: false,
        order: [[0, "desc"]], // Sort by first column (ignoring #)
        lengthChange: false,
        language: {
          search: "",
          searchPlaceholder: "Search..."
        },
        columnDefs: [
          {
            targets: 0, // First column
            type: "ignore-hash" // Use custom sort type
          },
          {
            targets: lastColIndex, // Last column (e.g., Actions)
            orderable: false
          }
        ],
        initComplete: function () {
          // Extract years from Date column
          const years = new Set();
          this.api()
            .column(dateColIndex)
            .data()
            .each(function (d) {
              const match = d.match(/\d{2}\/\d{2}\/(\d{4})/); // Match MM/DD/YYYY
              if (match) {
                years.add(match[1]); // Get year
              }
            });

          // Populate dropdown
          Array.from(years)
            .sort((a, b) => b - a)
            .forEach((year) => {
              yearSelect.append(`<option value="${year}">${year}</option>`);
            });

          // Append dropdown next to search box
          jQuery(`.dataTables_filter label`).before(yearSelect);
        }
      });

      // Year filter change event
      yearSelect.on("change", function () {
        const selectedYear = this.value;
        if (selectedYear) {
          dataTable
            .column(dateColIndex)
            .search(`/${selectedYear}$`, true, false)
            .draw();
        } else {
          dataTable.column(dateColIndex).search("").draw();
        }
      });

      // Hide pagination/info if only one page
      dataTable.on("draw", function () {
        const pageInfo = dataTable.page.info();
        const wrapper = $table.closest(".dataTables_wrapper");
        const pagination = wrapper.find(".dataTables_paginate");
        const infoText = wrapper.find(".dataTables_info");

        if (pageInfo.pages <= 1) {
          pagination.hide();
          infoText.hide();
        } else {
          pagination.show();
          infoText.show();
        }
      });

      // Trigger initial draw
      dataTable.draw();
    }
  }
});

}

function VerifyRecipientsDatatable() {
  ["failCSVData", "successCSVData", "newCSVData"].forEach((id) => {
    const div = document.getElementById(id);
    if (div && div.innerHTML.trim() !== "") {
      const tableEl = div.querySelector("table");
      if (tableEl && !jQuery(tableEl).hasClass("dataTable")) {
        const $table = jQuery(tableEl);

        // Initialize DataTable
        const dataTable = $table.DataTable({
          paging: false,
          scrollY: "500px",
          pageLength: 50,
          lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
          ],
          paging: false,
          fixedHeader: true,
          scrollCollapse: true,
          info: true,
          searching: true,
          responsive: true,
          deferRender: false,
          lengthChange: false,
          language: {
            search: "",
            searchPlaceholder: "Search..."
          },
          columnDefs: [
            {
              targets: -1, // -1 means "last column"
              orderable: false // disables sorting
            }
          ]
        });

        // Hide pagination & info if only 1 page
        dataTable.on("draw", function () {
          const pageInfo = dataTable.page.info();
          const wrapper = $table.closest(".dataTables_wrapper");
          const pagination = wrapper.find(".dataTables_paginate");
          const infoText = wrapper.find(".dataTables_info");

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

function process_group_popup(selectHtml = "") {
  if (selectHtml == "") {
    selectHtml = "Please wait while we process your request.";
  }
  Swal.fire({
    title: "Processing...",
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

jQuery("select").each(function ($) {
  var placeholderText =
    jQuery(this).data("error-message") || "Select an option";

  if (jQuery(this).hasClass("gfield_select")) {
    placeholderText = "Please select a type";
  }
  if (jQuery(this)[0].getAttribute('name') == 'input_8') {
  placeholderText = "Please select a user type";
  }
  if (jQuery(this)[0].getAttribute('name') == 'input_18') {
    placeholderText = "Please select an inquiry type";
  }

  jQuery(this).select2({
    placeholder: placeholderText,
    allowClear: false
  });
});

jQuery(document).ready(function ($) {
  affiliateDatatable();

  if (getURLParam("action") == "afficted-link" && getURLParam("token") != "") {
  }
  jQuery("#affiliate_select").select2({
    placeholder: "Select an Organization Below",
    matcher: function (params, data) {
      if (!params.term) {
        return data.text === "Honey from the Heart"
          ? $.extend({}, data, { sort: 0 })
          : data;
      }

      const term = params.term.toLowerCase();
      const text = data.text.toLowerCase();

      const bracketMatch = data.text.match(/\[([^\]]+)\]/);
      const bracketCode = bracketMatch ? bracketMatch[1].toLowerCase() : "";

      let matchScore = null;

      if (data.text === "Honey from the Heart") {
        matchScore = 1; // Always top
      } else if (bracketCode.includes(term)) {
        matchScore = 2; // Best match
      } else if (text.includes(term)) {
        matchScore = 3; // Fallback match
      }

      if (matchScore !== null) {
        return $.extend({}, data, { sort: matchScore });
      }

      return null;
    },
    sorter: function (data) {
      return data.sort((a, b) => (a.sort || 99) - (b.sort || 99));
    }
  });
});
jQuery(document).on("focus", ".select2-selection--single", function (e) {
  // Only open if not already open
  if (
    !jQuery(this)
      .closest(".select2-container")
      .hasClass("select2-container--open")
  ) {
    jQuery(this).closest(".select2-container").prev("select").select2("open");
  }
});

function initTippy() {
  document.querySelectorAll("[data-tippy]").forEach((el) => {
    if (el._tippy) {
      el._tippy.destroy(); // Destroy existing tooltip instance
    }
    tippy(el, {
      content: el.getAttribute("data-tippy"),
      theme: "translucent",
      animation: "fade",
      arrow: true,
      allowHTML: true,
      followCursor: true,
      trigger: "mouseenter"
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
      // minusBtn.disabled = value == 1;
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

const greetingTextareas = document.querySelectorAll(
  "#affiliate-gift-card-form textarea, #multiStepForm textarea, #recipient-manage-form form textarea, #recipient-order-manage-popup textarea, #affiliate-mission-statement-form textarea"
);

if (greetingTextareas.length) {
  greetingTextareas.forEach((textarea) => {
    let maxChars = 100;
    const textareaDiv = textarea.closest(".textarea-div"); // Find closest parent
    if (textareaDiv) {
      if (textarea.hasAttribute("data-limit")) {
        maxChars = textarea.getAttribute("data-limit");
      }
      const charCounter = textareaDiv.querySelector(".char-counter span");
      if (charCounter) {
        // Ensure charCounter exists
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

document.addEventListener("lity:open", function (event) {
  event.preventDefault();
  const popupOverlay = document.querySelector(".lity-wrap");

  if (popupOverlay) {
    popupOverlay.addEventListener("click", function (e) {
      if (e.target.classList.contains("lity-wrap")) {
        e.preventDefault(); // Prevents the default closing behavior
      }
    });
  }
});

document.addEventListener("lity:close", function (event) {
  event.preventDefault();
  // Get the closed modal's element
  const closedModal = event.target;

  // Check if the modal has an ID
  if (closedModal && closedModal.id) {
    const form = closedModal.querySelector("#recipient-manage-form form");
    if (form) {
      form.reset();
    }
  }
});

/*
Create new group Js Start
 */
const createGroupButtons = document.querySelectorAll(".createGroupButton");
if (createGroupButtons.length > 0) {
  createGroupButtons.forEach((button) => {
    button.addEventListener("click", function (event) {
      // Prevent the default action
      event.preventDefault();

      const target = event.target;
      const formType = target.closest("form").getAttribute("data-formtype");
      let groupNameInput;
      let groupIdInput;
      let groupName;
      let groupId = "";
      let groupFormDiv = "";

      const msg = target.closest("div").querySelector(".response-msg");
      msg.textContent = "";

      if (formType == "edit") {
        groupFormDiv = target.closest(".edit-group-form");
        groupNameInput = groupFormDiv.querySelector(".group_name");
        groupIdInput = groupFormDiv.querySelector(".group_id");
        groupName = groupNameInput.value;
        groupId = groupIdInput.value;
      }
      if (formType == "create") {
        // Get the group name input field inside the closest form
        groupFormDiv = target.closest(".recipient-group-form");
        groupNameInput = groupFormDiv.querySelector(".group_name");

        // Get the value from the input field
        groupName = groupNameInput.value;

        // Check if the input value is empty
        if (groupName.trim() === "") {
          msg.textContent = "Please enter a group name.";
          return;
        }
      }

      // Send AJAX request
      fetch(oam_ajax.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
          action: "create_group",
          group_name: groupName,
          group_status: formType,
          group_id: groupId
        })
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            console.log("Success:", data.data.message);
            msg.textContent = data.data.message;
            groupFormDiv.querySelector("form").reset();
            setTimeout(function () {
              window.location.reload();
            }, 1000);
          } else {
            console.error("Error:", data.data.message);
            msg.textContent = "Error: " + data.data.message;
          }
        })
        .catch((error) => {
          console.error("Fetch Error:", error);
          msg.textContent = "An error occurred while creating the group.";
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
document.addEventListener("click", function (event) {
  if (event.target.classList.contains("viewAllRecipientsPopupCheckout")) {
    if (event.target.classList.contains("viewAllRecipientsPopupCheckout")) {
      setTimeout(function () {
        const $dataTables_wrapper = jQuery(
          "#viewAllRecipientsPopupCheckout .dataTables_wrapper"
        );
        if ($dataTables_wrapper.length == 0) {
          const $table = jQuery("#viewAllRecipientsPopupCheckout table");
          $table.DataTable({
            paging: true,
            info: true,
            searching: true,
            responsive: true,
            deferRender: false,
            lengthChange: false,
            columnDefs: [{ targets: "_all" }]
          });
        }
        initTippy();
      }, 200);
    }
  }

  if (event.target.classList.contains("wcReOrderCustomerDashboard")) {
    event.preventDefault();
    const target = event.target;
    const userID = target.getAttribute("data-user");
    const orderid = target.getAttribute("data-orderid");

    // process_group_popup();

    fetch(oam_ajax.ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: new URLSearchParams({
        action: "wc_re_order_customer_dashboard",
        userID: userID,
        orderid: orderid,
        security: oam_ajax.nonce
      })
    })
      .then((response) => response.json())
      .then((data) => {
        process_group_popup();
        if (data.success) {
          Swal.fire({
            title: data.data.message,
            icon: "success",
            showConfirmButton: false,
            timerProgressBar: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          });
          setTimeout(function () {
            window.location.href = data.data.redirect_url;
          }, 1500);
        } else {
          Swal.fire({
            title: "Error",
            text: data.message,
            icon: "error"
          });
        }
      })
      .catch((error) => {
        Swal.fire({
          title: "Error",
          text: "An error occurred while deleting the group.",
          icon: "error"
        });
      });
  }

  if (event.target.classList.contains("deleteGroupButton")) {
    console.log("sas");
    event.preventDefault();
    const target = event.target;
    const groupID = target.getAttribute("data-groupid");
    const groupName = target.getAttribute("data-groupname") || "this recipient";

    Swal.fire({
      title: "Are you sure?",
      html:
        "You are removing <strong>" + groupName + "</strong> recipient list.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes",
      cancelButtonText: "Cancel",
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        process_group_popup(); // Call the popup function before deleting

        fetch(oam_ajax.ajax_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams({
            action: "deleted_group",
            group_id: groupID
          })
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              Swal.fire({
                title: data.message,
                icon: "success",
                showConfirmButton: false,
                timerProgressBar: false
              });
              setTimeout(function () {
                window.location.reload();
              }, 1500);
            } else {
              Swal.fire({
                title: "Error",
                text: data.message,
                icon: "error"
              });
            }
          })
          .catch((error) => {
            Swal.fire({
              title: "Error",
              text: "An error occurred while deleting the group.",
              icon: "error"
            });
          });
      }
    });
  }

  if (event.target.classList.contains("deleteIncompletedOrderButton")) {
    console.log("sas");
    event.preventDefault();
    const target = event.target;
    const groupID = target.getAttribute("data-id");
    const groupName =
      target.getAttribute("data-name") || "this Incomplete Order";

    Swal.fire({
      title: "Are you sure?",
      html:
        "You are removing <strong>" + groupName + "</strong> incomplete order.",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes",
      cancelButtonText: "Cancel",
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false,
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        process_group_popup(); // Call the popup function before deleting

        fetch(oam_ajax.ajax_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams({
            action: "delete_incompleted_order_button",
            id: groupID
          })
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              Swal.fire({
                title: data.data.message,
                icon: "success",
                showConfirmButton: false,
                timerProgressBar: false,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false
              });
              setTimeout(function () {
                window.location.reload();
              }, 1500);
            } else {
              Swal.fire({
                title: "Error",
                text: data.data.message,
                icon: "error"
              });
            }
          })
          .catch((error) => {
            Swal.fire({
              title: "Error",
              text: "An error occurred while deleting the group.",
              icon: "error"
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
const groupsList = document.querySelectorAll(".groups-list");
if (groupsList.length > 0) {
  groupsList.forEach((select) => {
    select.addEventListener("change", function (event) {
      event.preventDefault();
      const target = event.target; // This is the element that triggered the event
      const groupListWrapper = target.closest(".recipient-group-section");
      const editFormButton = groupListWrapper.querySelector(
        ".editGroupFormButton"
      );

      if (editFormButton) {
        editFormButton.style.display = "block";
      }

      const editgroupform = groupListWrapper.querySelector(".edit-group-form");
      if (editgroupform) {
        editgroupform.style.display = "none";
      }

      if (groupListWrapper) {
        const editFormWrapper = groupListWrapper.querySelector(
          ".edit-group-form-wrapper"
        );
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

const editGroupFormButton = document.querySelectorAll(".editGroupFormButton");
if (editGroupFormButton.length > 0) {
  editGroupFormButton.forEach((button) => {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      const target = event.target;
      target.style.display = "none";

      const groupName = target
        .closest(".recipient-group-list")
        .querySelector("select").selectedOptions[0].textContent;
      const groupId = target
        .closest(".recipient-group-list")
        .querySelector("select").value;
      const editgroupformwrapper = target.closest(".edit-group-form-wrapper");
      if (editgroupformwrapper) {
        const editFormWrapper =
          editgroupformwrapper.querySelector(".edit-group-form");
        if (editFormWrapper) {
          editFormWrapper.style.display = "block";
          editFormWrapper.querySelector(".group_name").value = groupName;
          editFormWrapper.querySelector(".group_id").value = groupId;
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

const createGroupFormButton = document.querySelectorAll(
  ".createGroupFormButton"
);
if (createGroupFormButton.length > 0) {
  createGroupFormButton.forEach((button) => {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      const target = event.target;
      target.style.display = "none";
      const groupListWrapper = target.closest(".recipient-group-section");
      if (groupListWrapper) {
        const createFormWrapper = groupListWrapper.querySelector(
          ".recipient-group-form"
        );
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

const uploadRecipientButton = document.querySelectorAll(
  ".uploadRecipientButton"
);
if (uploadRecipientButton.length > 0) {
  uploadRecipientButton.forEach((button) => {
    button.addEventListener("click", function (event) {
      event.preventDefault();
      const target = event.target;
      target.style.display = "none";
      const groupListWrapper = target.closest(".recipient-group-section");
      if (groupListWrapper) {
        const createFormWrapper = groupListWrapper.querySelector(
          ".upload-recipient-form"
        );
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
document.addEventListener("click", function (event) {
  if (event.target.id === "bulkMargeRecipient") {
    event.preventDefault();

    const duplicateCSVData = document.querySelector("#duplicateCSVData");
    const groups = duplicateCSVData.querySelectorAll(".group-header");

    const ids = []; // Store all IDs
    groups.forEach(function (group) {
      const groupId = group.getAttribute("data-group");
      console.log(groupId);
      const dataGroupTrs = duplicateCSVData.querySelectorAll(
        'tr[data-group="' + groupId + '"]:not(.group-header)'
      );

      let selectedData = Array.from(dataGroupTrs).find(
        (data) => data.getAttribute("data-verify") == "1"
      );

      // Fallback to data-verify="0" if no data-verify="1"
      if (!selectedData) {
        selectedData = Array.from(dataGroupTrs).find(
          (data) => data.getAttribute("data-verify") == "0"
        );
      }

      const firstId = selectedData
        ? selectedData.getAttribute("data-id")
        : null;

      console.log(firstId);
      // Collect all other IDs except the first
      const remainingIds = Array.from(dataGroupTrs)
        .map((data) => data.getAttribute("data-id"))
        .filter((id) => id !== firstId);

      ids.push(...remainingIds);
    });

    Swal.close();

    // AJAX request to pass the IDs to the 'bulkdelete' action
    if (ids.length > 0) {
      Swal.fire({
        title: "Are you sure?",
        text: "Keep 1 Entry and Delete Other Duplicate Entries",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
        cancelButtonText: "No",
        reverseButtons: true,
        allowOutsideClick: false,
        allowEscapeKey: false,
        allowEnterKey: false
      }).then((result) => {
        if (result.isConfirmed) {
          fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
              action: "bulk_deleted_recipient",
              ids: JSON.stringify(ids) // Pass the array of IDs as a JSON string
            })
          })
            .then((response) => response.json())
            .then((data) => {
              // Handle the response from the server
              if (data.success) {
                Swal.fire({
                  title: data.data.message,
                  icon: "success",
                  showConfirmButton: false,
                  timerProgressBar: false
                });

                setTimeout(function () {
                  window.location.reload();
                }, 1500);
              } else {
                Swal.fire({
                  title: "Error",
                  text: data.data.message,
                  icon: "error"
                });
              }
            })
            .catch((error) => {
              Swal.fire({
                title: "Error",
                text: "Error:",
                error,
                icon: "error"
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

document.addEventListener("click", function (event) {
  if (event.target.classList.contains("deleteRecipient")) {
    event.preventDefault();

    let popup_text = "You are removing ";
    let confirm_button_text = "Yes, remove the recipient";

    const duplicateCSVDataCheck = event.target.closest("#duplicateCSVData");
    if (duplicateCSVDataCheck) {
      popup_text = "You are removing a duplicate of ";
      confirm_button_text = "Yes, remove the duplicate";
    }

    let method = "process";
    const customer_dashboard_recipient_list = document.querySelector(
      "#customer-dashboard-recipient-list"
    );
    let group_id = 0;
    if (customer_dashboard_recipient_list) {
      method = "group";
      group_id = customer_dashboard_recipient_list.getAttribute("data-groupid");
    }

    const target = event.target;
    const recipientTr = target.closest("tr");
    const recipientID = recipientTr?.getAttribute("data-id");
    const recipientname = target.getAttribute("data-recipientname");

    if (!recipientID) {
      Swal.fire({
        title: "Error",
        text: "Recipient ID not found.",
        icon: "error"
      });
      return;
    }

    Swal.fire({
      title: "Are you sure?",
      text: popup_text + recipientname,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: confirm_button_text,
      cancelButtonText: "Cancel",
      reverseButtons: true,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(oam_ajax.ajax_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams({
            action: "deleted_recipient",
            id: recipientID,
            method: method
          })
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              Swal.fire({
                title: "Recipient removed successfully!",
                icon: "success",
                showConfirmButton: false,
                timerProgressBar: false
              });

              setTimeout(function () {
                window.location.reload();
              }, 1500);

              // Optionally, you can remove the row directly:
              // recipientTr.remove();
            } else {
              Swal.fire({
                title: "Error",
                text: data.data.message || "Failed to remove recipient.",
                icon: "error"
              });
            }
          })
          .catch(() => {
            Swal.fire({
              title: "Error",
              text: "An error occurred while removing the recipient.",
              icon: "error"
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
const recipientManageForm = document.querySelector(
  "#recipient-manage-form form"
);

function validateRecipientManageForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll(
    "input[required], select[required], textarea[required]"
  );

  requiredFields.forEach((field) => {
    let parentDiv = field.closest(".form-row"); // Ensure correct parent
    let errorMessage = parentDiv
      ? parentDiv.querySelector(".error-message")
      : null;

    if (!field.value.trim()) {
      field.style.border = "1px solid red";
      if (errorMessage) {
        errorMessage.textContent =
          field.getAttribute("data-error-message") || "This field is required.";
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

    if (!validateRecipientManageForm(this)) return;

    process_group_popup();

    const formData = new FormData(this);

    // Determine step and address verification
    let address_verified = 0;
    const step = document
      .querySelector(".step-nav-item.active")
      ?.getAttribute("data-step");
    if (step == 4) {
      address_verified = 1;
    }

    // Determine method and group/order ID
    let method = "process";
    let group_id = 0;

    const customerList = document.querySelector(
      "#customer-dashboard-recipient-list"
    );
    const orderData = document.querySelector("#recipient-order-data");

    if (customerList) {
      method = "group";
      group_id = customerList.getAttribute("data-groupid") || 0;
    }

    if (orderData) {
      method = "order";
      group_id = 0;
    }

    // Append additional data
    formData.append("action", "manage_recipient_form");
    formData.append("method", method);
    formData.append("group_id", group_id);
    formData.append("address_verified", address_verified);

    // AJAX request function
    const sendFormData = (fd) => {
      return fetch(oam_ajax.ajax_url, {
        method: "POST",
        body: fd
      }).then((res) => res.json());
    };

    // Initial request
    sendFormData(formData)
      .then((data) => {
        if (data.success) {
          Swal.fire({
            title: data.data.message,
            icon: "success",
            showConfirmButton: false,
            timerProgressBar: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          });
          setTimeout(() => {
            window.location.reload();
          }, 1500);

          // Close lity popup if open
          document.querySelector("[data-lity-close]")?.click();
        } else {
          // If invalid address, ask user confirmation
          Swal.fire({
            html:
              '<h2 class="swal2-title" style="padding-top: 0;">' +
              data.data.message +
              " <br><br>Please review and edit your address, or proceed with the address you have entered.</h2>",

            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Proceed with My Entered Address",
            cancelButtonText: "Review & Edit Address",
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          }).then((result) => {
            if (result.isConfirmed) {
              formData.append("invalid_address", 1);
              process_group_popup();
              sendFormData(formData)
                .then((data) => {
                  if (data.success) {
                    Swal.fire({
                      title: data.data.message,
                      icon: "success",
                      showConfirmButton: false,
                      timerProgressBar: false,
                      allowOutsideClick: false,
                      allowEscapeKey: false,
                      allowEnterKey: false
                    });

                    setTimeout(() => {
                      window.location.reload();
                    }, 1500);
                  } else {
                    Swal.fire({
                      title: "Error",
                      text: data.data.message || "Failed to remove recipients.",
                      icon: "error"
                    });
                  }
                })
                .catch(() => {
                  Swal.fire({
                    title: "Error",
                    text: "An error occurred while removing recipients.",
                    icon: "error"
                  });
                });
            }
          });
        }
      })
      .catch((error) => {
        console.error("Error during AJAX request:", error);
        Swal.fire({
          title: "Error",
          text: "An error occurred while processing the request.",
          icon: "error"
        });
      });
  });
}

/*
Edit button JS start
 */

document.addEventListener("click", function (event) {
  if (
    event.target.classList.contains("editRecipient") ||
    event.target.classList.contains("viewRecipient")
  ) {
    event.preventDefault();
    process_group_popup();

    const target = event.target;

    const recipientTr = target.closest("tr");
    let address_verified = 0;
    const form = document.querySelector("#recipient-manage-form form");
    form.reset();
    if (recipientTr) {
      document.querySelector(
        "#recipient-manage-popup .recipient-reasons"
      ).style.display = "none";
      const recipientID = recipientTr.getAttribute("data-id");

      if (recipientTr.hasAttribute("data-address_verified")) {
        address_verified = recipientTr.getAttribute("data-address_verified");
      }

      let method = "process";
      const customer_dashboard_recipient_list = document.querySelector(
        "#customer-dashboard-recipient-list"
      );
      if (customer_dashboard_recipient_list) {
        method = "group";
      }

      fetch(oam_ajax.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
          action: "get_recipient_base_id",
          id: recipientID,
          address_verified: address_verified,
          method: method
        })
      })
        .then((response) => response.json())
        .then((data) => {
          console.log(data);
          if (data.success) {
            const id = data.data.id;
            const full_name = data.data.full_name;
            const company_name = data.data.company_name;
            const address_1 = data.data.address_1;
            const address_2 = data.data.address_2;
            const city = data.data.city;
            const state = data.data.state;
            const zipcode = data.data.zipcode;
            const quantity = data.data.quantity;
            const greeting = data.data.greeting;
            let reasons = data.data.reasons;

            if (reasons == "Valid and deliverable address.") {
              reasons = "";
            }

            console.log(reasons);
            console.log(address_verified);
            if (event.target.classList.contains("editRecipient")) {
              form.querySelector("#recipient_id").value = id;
              form.querySelector("#full_name").value = full_name;
              form.querySelector("#company_name").value = company_name;
              form.querySelector("#address_1").value = address_1;
              form.querySelector("#address_2").value = address_2;
              form.querySelector("#city").value = city;
              form.querySelector("#state").value = state;
              form.querySelector("#zipcode").value = zipcode;
              form.querySelector("#quantity").value =
                quantity > 0 ? quantity : 1;
              form.querySelector("#greeting").value = greeting;
              form.querySelector('button[type="submit"]').innerHTML =
                "Update Recipient Details";
              form.querySelector(".textarea-div .char-counter span").innerHTML =
                100 - form.querySelector("#greeting").value.length;
              if (
                document.querySelector("#unverified-block") ||
                document.querySelector("#verified-block")
              ) {
                document.querySelector(".recipient-reasons").style.display =
                  "block";
                document.querySelector(".recipient-reasons").innerHTML =
                  reasons;
              }
            }

            if (event.target.classList.contains("viewRecipient")) {
              let html = "<ul>";
              html +=
                "<li><label>Full Name</label><span> " +
                (full_name ? full_name : "") +
                "</span></li>";
              html +=
                "<li><label>Company Name </label><span>" +
                (company_name ? company_name : "") +
                "</span></li>";
              html +=
                "<li><label>Mailing Address </label><span>" +
                (address_1 ? address_1 : "") +
                "</span></li>";
              html +=
                "<li><label>Suite/Apt# </label><span>" +
                (address_2 ? address_2 : "") +
                "</span></li>";
              html +=
                "<li><label>City </label><span>" +
                (city ? city : "") +
                "</span></li>";
              html +=
                "<li><label>State </label><span>" +
                (state ? state : "") +
                "</span></li>";
              html +=
                "<li><label>Zipcode </label><span>" +
                (zipcode ? zipcode : "") +
                "</span></li>";
              html +=
                "<li><label>Quantity </label><span>" +
                (quantity ? quantity : "") +
                "</span></li>";

              html += "</ul>";
              html +=
                "<div class='recipient-view-greeting-box'><label>Greeting </label><span>" +
                (greeting ? greeting : "") +
                "</span></div>";

              const viewpopup = document.querySelector(
                "#recipient-view-details-popup .recipient-view-details-wrapper"
              );
              viewpopup.innerHTML = html;
              form.querySelector('[type="submit"]').innerHTML =
                "Add Recipient to Cart";
            }
            setTimeout(function () {
              lity(event.target.getAttribute("data-popup"));
              if (event.target.classList.contains("editRecipient")) {
                form.querySelector('button[type="submit"]').innerHTML =
                  "Update Recipient Details";
              } else {
                form.querySelector('[type="submit"]').innerHTML =
                  "Add Recipient to Cart";
              }
              jQuery(form).find("#state").val(state).trigger("change");
            }, 250);
            Swal.close();
          } else {
            Swal.fire({
              title: "Error",
              text: data.data.message || "Failed to get recipient.",
              icon: "error"
            });
          }
        })
        .catch(() => {
          Swal.fire({
            title: "Error",
            text: "An error occurred while removing the recipient.",
            icon: "error"
          });
        });
    } else {
      form.reset();
      lity(event.target.getAttribute("data-popup"));
      Swal.close();
    }
  }

  if (event.target.classList.contains("viewSuccessRecipientsAlreadyOrder")) {
    event.preventDefault();

    const status = event.target.getAttribute("data-status");

    if (status == "0") {
      event.target.setAttribute("data-status", "1");
      event.target.textContent = "View All Recipients";
      event.target.setAttribute(
        "data-tippy",
        "Back to view all the verified recipients."
      );

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
    } else {
      event.target.setAttribute("data-status", "0");
      event.target.textContent = "View Already Ordered Recipients";
      event.target.setAttribute(
        "data-tippy",
        "View only the recipients that we found on another recent order."
      );
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
    setTimeout(() => {
      //  VerifyRecipientsDatatable();
    }, 300);
  }

  if (event.target.classList.contains("removeRecipientsAlreadyOrder")) {
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
      title:
        "Are you sure you want to remove " +
        count +
        " recipients who have already received a jar this year from the list?",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, remove them",
      cancelButtonText: "No, keep them",
      reverseButtons: true,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(oam_ajax.ajax_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams({
            action: "remove_recipients_already_order_this_year",
            ids: idList,
            security: oam_ajax.nonce
          })
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              Swal.fire({
                title: data.data.message,
                icon: "success",
                showConfirmButton: false,
                timerProgressBar: false
              });

              setTimeout(function () {
                window.location.reload();
              }, 1500);
            } else {
              Swal.fire({
                title: "Error",
                text: data.data.message || "Failed to recipients removed.",
                icon: "error"
              });
            }
          })
          .catch(() => {
            Swal.fire({
              title: "Error",
              text: "An error occurred while recipients removed.",
              icon: "error"
            });
          });
      }
    });
  }

  if (event.target.classList.contains("alreadyOrderButton")) {
    event.preventDefault();
    process_group_popup();
    const target = event.target;

    const recipientTr = target.closest("tr");

    if (recipientTr) {
      const alreadyorder = recipientTr.getAttribute("data-alreadyorder");
      const recipientname = target.getAttribute("data-recipientname");
      fetch(oam_ajax.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
          action: "get_alreadyorder_popup",
          id: alreadyorder,
          security: oam_ajax.nonce
        })
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            const viewAllAlreadyOrderPopup = document.querySelector(
              "#viewAllAlreadyOrderPopup"
            );

            viewAllAlreadyOrderPopup.querySelector("tbody").innerHTML =
              data.data.data;
            viewAllAlreadyOrderPopup.querySelector("h3 span").innerHTML =
              recipientname;
            setTimeout(function () {
              lity("#viewAllAlreadyOrderPopup");
            }, 250);
            Swal.close();
          }
        })
        .catch(() => {
          Swal.fire({
            title: "Error",
            text: "An error occurred while removing the recipient.",
            icon: "error"
          });
        });
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

  const viewSuccessRecipientsAlreadyOrder = document.querySelector(
    ".viewSuccessRecipientsAlreadyOrder"
  );
  const removeRecipientsAlreadyOrder = document.querySelector(
    ".removeRecipientsAlreadyOrder"
  );
  if (count != 0) {
    if (viewSuccessRecipientsAlreadyOrder) {
      viewSuccessRecipientsAlreadyOrder.style.display = "inline-block";
    }
    if (removeRecipientsAlreadyOrder) {
      removeRecipientsAlreadyOrder.style.display = "inline-block";
      removeRecipientsAlreadyOrder
        .closest("div")
        .querySelector(".vline").style.display = "inline-block";
    }
  } else {
    if (viewSuccessRecipientsAlreadyOrder) {
      viewSuccessRecipientsAlreadyOrder.style.display = "none";
    }
    if (removeRecipientsAlreadyOrder) {
      removeRecipientsAlreadyOrder.style.display = "none";
      removeRecipientsAlreadyOrder
        .closest("div")
        .querySelector(".vline").style.display = "none";
    }
  }
});

/*
Edit button JS END
*/
document.addEventListener("click", function (event) {
  if (event.target.id === "download-failed-recipient-csv") {
    event.preventDefault();
    process_group_popup();
    const process_id = getURLParam("pid");
    let recipient_group_id = "";
    const customer_dashboard_recipient_list = document.querySelector(
      "#customer-dashboard-recipient-list"
    );
    if (customer_dashboard_recipient_list) {
      recipient_group_id =
        customer_dashboard_recipient_list.getAttribute("data-groupid");
    }

    const params = new URLSearchParams({
      action: "download_failed_recipient",
      type: process_id ? "process" : "group",
      id: process_id || recipient_group_id
    });

    fetch(oam_ajax.ajax_url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: params.toString()
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          const a = document.createElement("a");
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
            allowEnterKey: false
          });
        }
      })
      .catch((error) => console.error("AJAX Error:", error));
  }
});

document.addEventListener("click", function (event) {
  if (event.target.classList.contains("affiliate-block-btn")) {
    event.preventDefault();
    let isBlocked = event.target.getAttribute("data-blocked");
    let action = isBlocked == 1 ? "block" : "unblock";
    let affiliateCode = event.target.getAttribute("data-affiliate");

    Swal.fire({
      title: "Are you sure you want to " + action + " this organization?",
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, I want",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        fetch(oam_ajax.ajax_url, {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: new URLSearchParams({
            action: "affiliate_status_toggle_block",
            affiliate_id: affiliateCode,
            status: isBlocked
          })
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              Swal.fire({
                title: "Organization status changed successfully!",
                icon: "success",
                showConfirmButton: false,
                timerProgressBar: false
              });

              setTimeout(function () {
                window.location.reload();
              }, 1500);
            } else {
              Swal.fire({
                title: "Error",
                text:
                  data.data.message ||
                  "Failed to change status for organization.",
                icon: "error"
              });
            }
          })
          .catch(() => {
            Swal.fire({
              title: "Error",
              text: "An error occurred while changing status for organization.",
              icon: "error"
            });
          });
      }
    });
  }
});

// affiliates manage
document.addEventListener("DOMContentLoaded", function () {
  const affiliateFilterButton = document.getElementById(
    "affiliate-filter-button"
  );
  const searchInput = document.getElementById("search-affiliates");
  const filterSelect = document.getElementById("filter-block-status");

  if (affiliateFilterButton && searchInput && filterSelect) {
    function fetchAffiliates() {
      process_group_popup(); // If you're using a popup loader

      const searchValue = searchInput.value.trim();
      const filterValue = filterSelect.value;

      const requestData = new URLSearchParams();
      requestData.append("action", "search_affiliates");
      requestData.append("search", searchValue);
      requestData.append("filter", filterValue);

      fetch(oam_ajax.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: requestData.toString()
      })
        .then((response) => response.text())
        .then((data) => {
          // Look for the container again after fetch
          const wrapper = document.getElementById("affiliate-results");
          if (wrapper) {
            wrapper.outerHTML = data; // Replace the full block
          } else {
            console.warn("Element with id 'affiliate-results' not found.");
            Swal.fire({
              icon: "error",
              title: "Rendering Error",
              text: "Could not find the container to display affiliates."
            });
          }
          setTimeout(() => Swal.close(), 500);
        })
        .catch((error) => {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "Error fetching affiliates: " + error.message
          });
        });
    }

    affiliateFilterButton.addEventListener("click", function (event) {
      event.preventDefault();
      fetchAffiliates();
    });
  }
});

//Group Listing code
jQuery(document).ready(function ($) {
  const $table = $("#group-recipient-table");

  const dataTable = $table.DataTable({
    processing: true,
    serverSide: true,
    pageLength: 50,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100]
    ],
    ajax: {
      url: oam_ajax.ajax_url,
      type: "POST",
      data: function (d) {
        d.action = "orthoney_group_recipient_list_ajax";
        d.security = oam_ajax.nonce;
      }
    },
    columns: [
      { data: "id" },
      { data: "name" },
      { data: "recipient_count" },
      { data: "date" },
      { data: "action", orderable: false, searchable: false }
    ],
    drawCallback: function () {
      if (typeof initTippy === "function") {
        initTippy();
      }
    },
    language: {
      emptyTable: "No recipient group found"
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "csvHtml5",
        text: "Export CSV",
        filename: "group_recipient_list_export",
        exportOptions: {
          columns: ":not(:last-child)"
        }
      }
    ]
  });
});

jQuery(document).ready(function ($) {
  let currentRequest = null;
  let organizationSearch = "";
  let organizationCodeSearch = "";

  if (!$("#admin-customer-table").hasClass("dt-initialized")) {
    $("#admin-customer-table").addClass("dt-initialized");

    const table = new DataTable("#admin-customer-table", {
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        data: function (d) {
          d.action = "orthoney_admin_get_customers_data";
          d.organization_search = organizationSearch;
          d.organization_code_search = organizationCodeSearch;
        },
        beforeSend: function (jqXHR) {
          if (currentRequest) {
            currentRequest.abort();
          }
          currentRequest = jqXHR;

          const $tbody = $("#admin-customer-table tbody");
          const colspan = $("#admin-customer-table thead th").length;

          $tbody
            .hide()
            .html(
              `
            <tr class="custom-loading-row">
              <td colspan="${colspan}" style="text-align:center; font-weight:bold; padding:20px;">
                Loading customer data, please wait...
              </td>
            </tr>
          `
            )
            .show();
        },
        complete: function () {
          currentRequest = null;
          setTimeout(() => {
            $("#admin-customer-table tbody").show();
          }, 100);
        },
        error: function (xhr, status) {
          if (status !== "abort") {
            console.error("AJAX error occurred:", status);
          }
        }
      },
      language: {
        search: ""
      },
      columns: [
        { data: "id" },
        { data: "name" },
        { data: "organizations" },
        { data: "action" }
      ],
      columnDefs: [
        { targets: 0, orderable: false, searchable: false },
        { targets: 1, orderable: false },
        { targets: 2, orderable: false, searchable: false },
        { targets: -1, orderable: false, searchable: false }
      ],
      responsive: true,
      processing: true,
      serverSide: true,
      paging: true,
      searching: true,
      ordering: false,

      // ✅ Use initComplete to inject custom filters
      initComplete: function () {
        const $filterContainer = $("#admin-customer-table_filter");

        const orgInput = $(
          '<input type="text" placeholder="Search by Org Name" style="margin-right: 10px;">'
        ).on("keyup", function () {
          organizationSearch = $(this).val().trim();
          if (
            organizationSearch.length >= 3 ||
            organizationSearch.length === 0
          ) {
            if (currentRequest) currentRequest.abort();
            table.ajax.reload();
          }
        });

        const orgCodeInput = $(
          '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
        ).on("keyup", function () {
          organizationCodeSearch = $(this).val().trim();
          if (
            organizationCodeSearch.length >= 3 ||
            organizationCodeSearch.length === 0
          ) {
            if (currentRequest) currentRequest.abort();
            table.ajax.reload();
          }
        });

        // const customerInput = $('<input type="text" placeholder="Search by Customer Name" style="margin-right: 10px;">')
        //   .on('keyup', function () {
        //     customerSearch = $(this).val().trim();
        //     if (customerSearch.length >= 3 || customerSearch.length === 0) {
        //       if (currentRequest) currentRequest.abort();
        //       table.ajax.reload();
        //     }
        //   });

        // Prepend inputs to the filter container
        $filterContainer.prepend(orgCodeInput).prepend(orgInput);
        $filterContainer
          .find('input[type="search"]')
          .attr("placeholder", "Search by Customer Name Or Email");

        // Optional: Override default search input with debounce
        const searchBox = $filterContainer.find('input[type="search"]');
        let typingTimer;

        searchBox.off().on("input", function () {
          clearTimeout(typingTimer);
          const value = this.value;

          typingTimer = setTimeout(() => {
            if (value.length >= 3 || value.length === 0) {
              if (currentRequest) currentRequest.abort();
              table.search(value).draw();
            }
          }, 300);
        });
      }
    });
  }
});

/**
 *
 *
 */
jQuery(document).ready(function ($) {
  let currentRequest = null;
  let organizationCodeSearch = "";

  const table = new DataTable("#admin-sales-representative-table", {
    pageLength: 50,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100]
    ],
    ajax: {
      url: oam_ajax.ajax_url,
      type: "POST",
      data: function (d) {
        d.action = "orthoney_admin_get_sales_representative_data";
        d.organization_code_search = organizationCodeSearch;
      },
      beforeSend: function () {
        if (currentRequest) {
          currentRequest.abort(); // Abort previous
        }
      },
      dataSrc: function (json) {
        return json.data || [];
      },
      complete: function () {
        currentRequest = null;
      },
      error: function (xhr, textStatus) {
        if (textStatus !== "abort") {
          console.error("AJAX error: ", textStatus);
        }
      },
      xhr: function () {
        currentRequest = $.ajaxSettings.xhr();
        return currentRequest;
      }
    },
    columns: [
      { data: "id" },
      { data: "name" },
      { data: "email" },
      { data: "organizations" },
      { data: "action" }
    ],
    columnDefs: [
      { targets: -1, orderable: false, searchable: false },
      { targets: 2, visible: false, searchable: true }
    ],
    language: {
      processing: `
        <div class="loader multiStepForm" style="display:block">
            <div>
                <h2 class="swal2-title">Processing...</h2>
                <div class="swal2-html-container">Please wait while we process your request.</div>
                <div class="loader-5"></div>
            </div>
        </div>
      `,
      search: ""
    },

    ordering: false,
    responsive: true,
    processing: true,
    serverSide: true,
    paging: true,
    searching: true,

    // 👇 Custom input injected here after DataTable renders
    initComplete: function () {
      const $filterContainer = $("#admin-sales-representative-table_filter");

      // Change default search box placeholder
      $filterContainer
        .find('input[type="search"]')
        .attr("placeholder", "Search by CSR Name Or Email");

      // Create custom organization code search box
      const orgCodeInput = $(
        '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
      ).on("input", function () {
        organizationCodeSearch = $(this).val();
        table.ajax.reload();
      });

      // Insert it before default search
      $filterContainer.prepend(orgCodeInput);
    }
  });
});

/**
 *
 *
 */
jQuery(document).ready(function ($) {
  let selectedStatus = "";
  let selectedsessionStatus = "";
  let organizationSearch = "";
  let organizationCodeSearch = "";
  let currentAjaxRequest = null;

  if (!$("#admin-organizations-table").hasClass("dt-initialized")) {
    $("#admin-organizations-table").addClass("dt-initialized");

    const orgInput = $(
      '<input type="text" placeholder="Search by Org Name" style="margin-right: 10px;">'
    ).on("keyup", function () {
      organizationSearch = $(this).val().trim();
      table.ajax.reload();
    });

    const orgCodeInput = $(
      '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
    ).on("keyup", function () {
      organizationCodeSearch = $(this).val().trim();
      table.ajax.reload();
    });

    const table = new DataTable("#admin-organizations-table", {
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",

        beforeSend: function () {
          if (currentAjaxRequest && currentAjaxRequest.readyState !== 4) {
            currentAjaxRequest.abort();
          }
        },

        data: function (d) {
          d.action = "orthoney_admin_get_organizations_data";
          d.status_filter = selectedStatus;
          d.session_status_filter = selectedsessionStatus;
          d.organization_search = organizationSearch;
          d.organization_code_search = organizationCodeSearch;
        },

        dataSrc: function (json) {
          return json.data || [];
        },

        xhr: function () {
          currentAjaxRequest = $.ajaxSettings.xhr();
          return currentAjaxRequest;
        }
      },

      columns: [
        { data: "code" },
        { data: "organization" },
        { data: "organization_admin" },
        { data: "csr_name" },
        { data: "new_organization" },
        { data: "status" },
        { data: "price" },
        { data: "login" }
      ],

      columnDefs: [
        { targets: 0, visible: true, searchable: true, orderable: true },
        { targets: 1, width: "210px", orderable: false },
        { targets: 2, width: "210px", orderable: false },
        { targets: 3, width: "210px", orderable: false },
        { targets: 4, orderable: false, searchable: false },
        { targets: 5, visible: false, searchable: false, orderable: false },
        { targets: 6, searchable: false, orderable: false },
        { targets: 7, orderable: false, width: "100px" }
      ],

      language: {
        processing: `
          <div class="loader multiStepForm" style="display:block">
            <div>
              <h2 class="swal2-title">Processing...</h2>
              <div class="swal2-html-container">Please wait while we process your request.</div>
              <div class="loader-5"></div>
            </div>
          </div>`
      },

      processing: true,
      serverSide: true,
      paging: true,
      searching: true,
      responsive: true,
      scrollX: true,
      autoWidth: true,

      initComplete: function () {
        const api = this.api();
        const statusColIndex = 5;
        const statusSet = new Set();

        api
          .column(statusColIndex)
          .data()
          .each(function (d) {
            if (d && d.trim() !== "") {
              statusSet.add(d);
            }
          });

        // Dropdown for session status (Static: Active/Deactivate)
        const accountStatusSelect = $(`
          <select style="margin-right: 10px;">
            <option value="">All Account Statuses</option>
            <option value="active">Active</option>
            <option value="deactivate">Deactivate</option>
          </select>
        `).on("change", function () {
          selectedsessionStatus = $(this).val();
          table.ajax.reload();
        });

        // Dropdown for dynamic organization status
        const statusSelect = $(
          '<select style="margin-right: 10px;"><option value="">Filter by Status</option></select>'
        ).on("change", function () {
          selectedStatus = $(this).val();
          table.ajax.reload();
        });

        Array.from(statusSet)
          .sort()
          .forEach((status) => {
            statusSelect.append(`<option value="${status}">${status}</option>`);
          });

        const $filterArea = $(".dataTables_filter");
        if (!$filterArea.hasClass("custom-filter-added")) {
          $filterArea.addClass("custom-filter-added");
          $filterArea.prepend(statusSelect);
          $filterArea.prepend(orgInput);
          $filterArea.prepend(accountStatusSelect);
          $filterArea.prepend(orgCodeInput);

          $filterArea
            .find("label")
            .contents()
            .filter(function () {
              return this.nodeType === 3;
            })
            .remove();

          $filterArea
            .find('input[type="search"]')
            .attr("placeholder", "Search");
        }
      }
    });
  }
});

jQuery(document).ready(function ($) {
  let currentRequest = null;
  let organizationSearch = "";
  let organizationCodeSearch = "";

  if (!$("#admin-organizations-commission-table").hasClass("dt-initialized")) {
    $("#admin-organizations-commission-table").addClass("dt-initialized");

    const table = new DataTable("#admin-organizations-commission-table", {
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        data: function (d) {
          d.action = "orthoney_admin_get_organizations_commission_data";
          d.organization_search = organizationSearch;
          d.organization_code_search = organizationCodeSearch;
        },
        beforeSend: function (jqXHR) {
          if (currentRequest) {
            currentRequest.abort();
          }
          currentRequest = jqXHR;

          const $tbody = $("#admin-organizations-commission-table tbody");
          const colspan = $(
            "#admin-organizations-commission-table thead th"
          ).length;

          $tbody
            .hide()
            .html(
              `
            <tr class="custom-loading-row">
              <td colspan="${colspan}" style="text-align:center; font-weight:bold; padding:20px;">
                Loading organization data, please wait...
              </td>
            </tr>
          `
            )
            .show();
        },
        complete: function () {
          currentRequest = null;
          setTimeout(() => {
            $("#admin-organizations-commission-table tbody").show();
          }, 100);
        },
        error: function (xhr, status) {
          if (status !== "abort") {
            console.error("AJAX error occurred:", status);
          }
        }
      },
      language: {
        search: ""
      },
      columns: [
        { data: "organization" },
        { data: "total_order" },
        { data: "total_qty" },
        { data: "cost" },
        { data: "dist_cost" },
        { data: "unit_profit" },
        { data: "total_commission" }
      ],
      columnDefs: [{ targets: 0, width: "210px", orderable: true }],
      processing: true,
      serverSide: true,
      paging: true,
      searching: true,
      ordering: false,
      responsive: true,
      scrollX: true,
      autoWidth: false,

      initComplete: function () {
        const $filterContainer = $(
          "#admin-organizations-commission-table_filter"
        );

        const orgInput = $(
          '<input type="text" placeholder="Search by Org Name" style="margin-right: 10px;">'
        ).on("keyup", function () {
          organizationSearch = $(this).val().trim();
          if (
            organizationSearch.length >= 3 ||
            organizationSearch.length === 0
          ) {
            if (currentRequest) currentRequest.abort();
            table.ajax.reload();
          }
        });

        const orgCodeInput = $(
          '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
        ).on("keyup", function () {
          organizationCodeSearch = $(this).val().trim();
          if (
            organizationCodeSearch.length >= 3 ||
            organizationCodeSearch.length === 0
          ) {
            if (currentRequest) currentRequest.abort();
            table.ajax.reload();
          }
        });

        $filterContainer.prepend(orgCodeInput).prepend(orgInput);
        $filterContainer
          .find('input[type="search"]')
          .attr("placeholder", "Search");

        // Optional debounce for default search input
        const searchBox = $filterContainer.find('input[type="search"]');
        let typingTimer;

        searchBox.off().on("input", function () {
          clearTimeout(typingTimer);
          const value = this.value;

          typingTimer = setTimeout(() => {
            if (value.length >= 3 || value.length === 0) {
              if (currentRequest) currentRequest.abort();
              table.search(value).draw();
            }
          }, 300);
        });
      }
    });
  }
});

jQuery(document).ready(function ($) {
  let currentRequest = null;
  let organizationSearch = "";
  let organizationCodeSearch = "";
  let selectedStatus = "";

  // Add Org Name and Org Code input fields
  const orgInput = $(
    '<input type="text" placeholder="Search by Org Name" style="margin-right: 10px;">'
  ).on("keyup", function () {
    organizationSearch = $(this).val().trim();
    if (organizationSearch.length >= 3 || organizationSearch.length === 0) {
      if (currentRequest) currentRequest.abort();
      table.ajax.reload();
    }
  });

  const orgCodeInput = $(
    '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
  ).on("keyup", function () {
    organizationCodeSearch = $(this).val().trim();
    if (
      organizationCodeSearch.length >= 3 ||
      organizationCodeSearch.length === 0
    ) {
      if (currentRequest) currentRequest.abort();
      table.ajax.reload();
    }
  });

  const table = $("#sales-representative-affiliate-commission-table").DataTable(
    {
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      serverSide: true,
      processing: true,
      paging: true,
      searching: true,
      ordering: true,
      autoWidth: false,
      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        data: function (d) {
          d.action = "get_affiliates_commission_list_ajax";
          d.nonce = oam_ajax.nonce;
          d.organization_search = organizationSearch;
          d.organization_code_search = organizationCodeSearch;
          d.status_filter = selectedStatus;
        },
        beforeSend: function (jqXHR) {
          if (currentRequest) currentRequest.abort();
          currentRequest = jqXHR;

          const $tbody = $(
            "#sales-representative-affiliate-commission-table tbody"
          );
          const colspan = $(
            "#sales-representative-affiliate-commission-table thead th"
          ).length;

          $tbody
            .hide()
            .html(
              `
          <tr class="custom-loading-row">
            <td colspan="${colspan}" style="text-align:center; font-weight:bold; padding:20px;">
              Loading organization commission data, please wait...
            </td>
          </tr>
        `
            )
            .show();
        },
        complete: function () {
          currentRequest = null;
          setTimeout(() => {
            $("#sales-representative-affiliate-commission-table tbody").show();
          }, 100);
        },
        error: function (xhr, status) {
          if (status !== "abort") {
            console.error("AJAX error occurred:", status);
          }
        }
      },
      columns: [
        { data: "organization" },
        { data: "total_order" },
        { data: "total_qty" },
        { data: "cost" },
        { data: "dist_cost" },
        { data: "unit_profit" },
        { data: "total_commission" }
      ],
      columnDefs: [
        { targets: 0, width: "220px" },
        { targets: -1, orderable: false }
      ],
      language: {
        search: "",
        searchPlaceholder: "Search...",
        processing: `
        <div class="loader multiStepForm" style="display:block">
          <div>
            <h2 class="swal2-title">Processing...</h2>
            <div class="swal2-html-container">Please wait while we process your request.</div>
            <div class="loader-5"></div>
          </div>
        </div>`
      },
      initComplete: function () {
        const api = this.api();
        const $filterWrapper = $(
          "#sales-representative-affiliate-commission-table_filter"
        );

        // Clear default search label
        $filterWrapper
          .find("label")
          .contents()
          .filter(function () {
            return this.nodeType === 3;
          })
          .remove();

        // Append custom filters
        $filterWrapper.prepend(orgCodeInput).prepend(orgInput);

        // Optional: Attach debounce to default search box
        const searchBox = $filterWrapper.find('input[type="search"]');
        let typingTimer;

        searchBox.off().on("input", function () {
          clearTimeout(typingTimer);
          const value = this.value;

          typingTimer = setTimeout(() => {
            if (value.length >= 3 || value.length === 0) {
              if (currentRequest) currentRequest.abort();
              table.search(value).draw();
            }
          }, 300);
        });
      }
    }
  );
});

/***
 *
 *
 *
 */

jQuery(document).ready(function ($) {
  let selectedStatus = "";
  let organizationSearch = "";
  let organizationCodeSearch = "";
  let currentRequest = null;

  // Create Org Name input
  const orgInput = $(
    '<input type="text" placeholder="Search by Org Name" style="margin-right: 10px;">'
  ).on("keyup", function () {
    organizationSearch = $(this).val().trim();
    if (organizationSearch.length >= 3 || organizationSearch.length === 0) {
      if (currentRequest) currentRequest.abort();
      table.ajax.reload();
    }
  });

  // Create Org Code input
  const orgCodeInput = $(
    '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
  ).on("keyup", function () {
    organizationCodeSearch = $(this).val().trim();
    if (
      organizationCodeSearch.length >= 3 ||
      organizationCodeSearch.length === 0
    ) {
      if (currentRequest) currentRequest.abort();
      table.ajax.reload();
    }
  });

  // Initialize DataTable
  const table = $("#sales-representative-affiliate-table").DataTable({
    pageLength: 50,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100]
    ],
    serverSide: true,
    processing: true,
    paging: true,
    searching: true,
    ordering: false,
    ajax: {
      url: oam_ajax.ajax_url,
      type: "POST",
      data: function (d) {
        d.action = "get_affiliates_list_ajax";
        d.nonce = oam_ajax.nonce;
        d.organization_search = organizationSearch;
        d.organization_code_search = organizationCodeSearch;
        d.status_filter = selectedStatus;
      },
      beforeSend: function (jqXHR) {
        if (currentRequest) {
          currentRequest.abort();
        }
        currentRequest = jqXHR;

        const $tbody = $("#sales-representative-affiliate-table tbody");
        const colspan = $(
          "#sales-representative-affiliate-table thead th"
        ).length;

        $tbody
          .hide()
          .html(
            `
          <tr class="custom-loading-row">
            <td colspan="${colspan}" style="text-align:center; font-weight:bold; padding:20px;">
              Loading organizations data, please wait...
            </td>
          </tr>
        `
          )
          .show();
      },
      complete: function () {
        currentRequest = null;
        setTimeout(() => {
          $("#sales-representative-affiliate-table tbody").show();
        }, 100);
      },
      error: function (xhr, error, thrown) {
        if (error !== "abort") {
          console.error("AJAX Error:", error);
        }
      }
    },
    columns: [
      { data: "code" },
      { data: "organization" },
      { data: "organization_admin" },
      { data: "new_organization" },
      { data: "status" },
      { data: "price" },
      // { data: "commission" },
      { data: "login" }
    ],
    columnDefs: [
      { targets: 1, width: "220px", searchable: true },
      { targets: 2, width: "220px" },
      { targets: -1, orderable: false },
      { targets: 4, visible: false, searchable: true },
      { targets: 0, visible: true, searchable: true }
    ],
    language: {
      search: "",
      searchPlaceholder: "Search...",
      processing: `
        <div class="loader multiStepForm" style="display:block">
          <div>
            <h2 class="swal2-title">Processing...</h2>
            <div class="swal2-html-container">Please wait while we process your request.</div>
            <div class="loader-5"></div>
          </div>
        </div>`
    },
    initComplete: function () {
      const api = this.api();
      const $filterWrapper = $(".dataTables_filter");

      // Create status filter dropdown
      const statusDropdown = $(`
    <select style="margin-right: 10px;">
      <option value="">Season Status</option>
      <option value="active">Activated</option>
      <option value="deactivate">Deactivated</option>
    </select>
  `).on("change", function () {
        selectedStatus = $(this).val();
        if (currentRequest) currentRequest.abort();
        table.ajax.reload();
      });

      // Remove default label text
      $filterWrapper
        .find("label")
        .contents()
        .filter(function () {
          return this.nodeType === 3;
        })
        .remove();

      // Inject custom filters
      $filterWrapper
        .prepend(statusDropdown)
        .prepend(orgCodeInput)
        .prepend(orgInput);

      // Optional: Update default search input placeholder
      $filterWrapper
        .find('input[type="search"]')
        .attr("placeholder", "Search...");
    }
  });
});

/***
 *
 *
 *
 */
jQuery(document).ready(function ($) {
  let organizationSearch = "";
  let organizationCodeSearch = "";
  let currentRequest = null;

  if (!$("#sales-representative-customer-table").hasClass("dt-initialized")) {
    $("#sales-representative-customer-table").addClass("dt-initialized");

    const table = new DataTable("#sales-representative-customer-table", {
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      serverSide: true,
      processing: true,
      paging: true,
      searching: true,
      responsive: true,
      ordering: false,

      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        data: function (d) {
          return $.extend({}, d, {
            action: "get_filtered_customers",
            nonce: oam_ajax.nonce,
            organization_search: organizationSearch,
            organization_code_search: organizationCodeSearch
          });
        },
        dataSrc: function (response) {
          return response.data || [];
        },
        beforeSend: function () {
          const $tbody = $("#sales-representative-customer-table tbody");
          const colspan = $(
            "#sales-representative-customer-table thead th"
          ).length;
          $tbody
            .hide()
            .html(
              `
            <tr class="custom-loading-row">
              <td colspan="${colspan}" style="text-align:center; font-weight:bold; padding:20px;">
                Loading customer data, please wait...
              </td>
            </tr>
          `
            )
            .show();
        },
        error: function (xhr, status) {
          if (status !== "abort") {
            console.error("AJAX error:", status);
          }
        },
        complete: function () {
          currentRequest = null;
          setTimeout(() => {
            $("#sales-representative-customer-table tbody").show();
          }, 100);
        }
      },

      columns: [{ data: "name" }, { data: "email" }, { data: "action" }],
      columnDefs: [
        { targets: 0, width: "400px", orderable: false, searchable: true },
        { targets: 1, width: "400px", orderable: false, searchable: false },
        { targets: -1, width: "80px", orderable: false, searchable: false }
      ],
      language: {
        search: ""
      },

      initComplete: function () {
        const $filterContainer = $(
          "#sales-representative-customer-table_filter"
        );

        const orgInput = $(
          '<input type="text" placeholder="Search by Org Name" style="margin-right: 10px;">'
        ).on("keyup", function () {
          organizationSearch = $(this).val().trim();
          if (
            organizationSearch.length >= 3 ||
            organizationSearch.length === 0
          ) {
            if (currentRequest) currentRequest.abort();
            table.ajax.reload();
          }
        });

        const orgCodeInput = $(
          '<input type="text" placeholder="Search by Org Code" style="margin-right: 10px;">'
        ).on("keyup", function () {
          organizationCodeSearch = $(this).val().trim();
          if (
            organizationCodeSearch.length >= 3 ||
            organizationCodeSearch.length === 0
          ) {
            if (currentRequest) currentRequest.abort();
            table.ajax.reload();
          }
        });

        $filterContainer.prepend(orgCodeInput).prepend(orgInput);

        const searchBox = $filterContainer.find('input[type="search"]');
        searchBox.attr("placeholder", "Search Customers");
      }
    });
  }
});

//incomplete order process code
jQuery(document).ready(function ($) {
  const $table = $("#incomplete-order-table");
  const failedFlag = $table.data("failed") ? 1 : 0;

  const emptyMessage =
    failedFlag === 1
      ? "No Failed Recipients list found"
      : "No incomplete orders found";

  // Conditionally define columns
  const columns = [{ data: "id" }, { data: "name" }, { data: "ordered_by" }];

  if (failedFlag !== 1) {
    columns.push({ data: "current_step" });
  }

  columns.push(
    { data: "date" },
    { data: "action", orderable: false, searchable: false }
  );

  const dataTable = $table.DataTable({
    pageLength: 50,
    lengthMenu: [
      [10, 25, 50, 100],
      [10, 25, 50, 100]
    ],
    processing: true,
    serverSide: true,
    ajax: {
      url: oam_ajax.ajax_url,
      type: "POST",
      data: function (d) {
        d.action = "orthoney_incomplete_order_process_ajax";
        d.security = oam_ajax.nonce;
        d.failed = failedFlag;
      }
    },
    columns: columns,
    drawCallback: function () {
      if (typeof initTippy === "function") {
        initTippy();
      }
    },
    language: {
      emptyTable: emptyMessage
    },
    dom: "Bfrtip",
    buttons: [
      {
        extend: "csvHtml5",
        text: "Export CSV",
        filename: "incomplete_orders_export",
        exportOptions: {
          columns: ":not(:last-child)" // Exclude the action column
        }
      }
    ]
  });
});

/**
 * Recipient Order Start
 */

document.addEventListener("DOMContentLoaded", function () {
  const recipientOrderData = document.querySelector("#recipient-order-data");

  if (recipientOrderData) {
    setTimeout(() => {
      jQuery("#recipient-order-data table").DataTable({
        pageLength: 50,
        lengthMenu: [
          [10, 25, 50, 100],
          [10, 25, 50, 100]
        ],
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

document.addEventListener("click", function (event) {
  const target = event.target;
  const isEdit = target.classList.contains("editRecipientOrder");
  const isView = target.classList.contains("viewRecipientOrder");

  if (!isEdit && !isView) return;

  event.preventDefault();
  process_group_popup();

  const form = document.querySelector("#recipient-manage-order-form form");
  form.reset();

  const orderID = target.getAttribute("data-order");

  fetch(oam_ajax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "get_recipient_order_base_id",
      id: orderID
    })
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) {
        throw new Error(data.data.message || "Failed to get recipient.");
      }

      const d = data.data;

      if (isEdit) {
        const popuptitle = document.querySelector(
          "#recipient-order-manage-popup .popup-title span"
        );
        popuptitle.innerHTML = "#" + orderID;
        const fields = [
          "order_id",
          "full_name",
          "company_name",
          "address_1",
          "address_2",
          "city",
          "state",
          "zipcode",
          "quantity",
          "greeting"
        ];
        fields.forEach((field) => {
          const input = form.querySelector(`#${field}`);
          if (input) input.value = d[field] || "";
        });
        form.querySelector('button[type="submit"]').innerHTML =
          "Edit Recipient Order Details";
        document.querySelector(".textarea-div .char-counter span").innerHTML =
          100 - d.greeting.length;
      }

      if (isView) {
        const viewpopup = document.querySelector(
          "#recipient-order-edit-popup .recipient-view-details-wrapper"
        );
        const popuptitle = document.querySelector(
          "#recipient-order-edit-popup .popup-title span"
        );
        popuptitle.innerHTML = "#" + orderID;
        viewpopup.innerHTML = `
                    <ul>
                        <li><label>Full Name</label><span>${
                          d.full_name || ""
                        }</span></li>
                        <li><label>Company Name</label><span>${
                          d.company_name || ""
                        }</span></li>
                        <li><label>Mailing Address</label><span>${
                          d.address_1 || ""
                        }</span></li>
                        <li><label>Suite/Apt#</label><span>${
                          d.address_2 || ""
                        }</span></li>
                        <li><label>City</label><span>${d.city || ""}</span></li>
                        <li><label>State</label><span>${
                          d.full_state || ""
                        }</span></li>
                        <li><label>Zipcode</label><span>${
                          d.zipcode || 0
                        }</span></li>
                        <li><label>Quantity</label><span>${
                          d.quantity || 0
                        }</span></li>
                    </ul>
                    <div class='recipient-view-greeting-box'>
                        <label>Greeting</label><span>${d.greeting || ""}</span>
                    </div>`;
      }

      setTimeout(() => {
        jQuery("#state").select2({
          placeholder: "Please select a state",
          allowClear: false
        });
        lity(target.getAttribute("data-popup"));

        const submitButton = form.querySelector('button[type="submit"]');
        if (isEdit) {
          submitButton.style.display = "block";
          submitButton.innerHTML = "Edit Recipient Order Details";
        } else {
          submitButton.style.display = "none";
        }

        jQuery(form).find("#state").val(d.state).trigger("change");
      }, 250);

      Swal.close();
    })
    .catch((error) => {
      Swal.fire({
        title: "Error",
        text:
          error.message || "An error occurred while retrieving the recipient.",
        icon: "error"
      });
    });
});

const recipientOrderManageForm = document.querySelector(
  "#recipient-manage-order-form form"
);

function validateRecipientOrderManageForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll(
    "input[required], select[required], textarea[required]"
  );

  requiredFields.forEach((field) => {
    const parentDiv = field.closest(".form-row");
    const errorMessage = parentDiv?.querySelector(".error-message");

    if (!field.value.trim()) {
      field.style.border = "1px solid red";
      if (errorMessage) {
        errorMessage.textContent =
          field.getAttribute("data-error-message") || "This field is required.";
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
    formData.append("action", "manage_recipient_order_form");
    formData.append("security", oam_ajax.nonce); // Append nonce here

    fetch(oam_ajax.ajax_url, {
      method: "POST",
      body: formData
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          // If invalid address, ask user confirmation
          return Swal.fire({
            html:
              '<h2 class="swal2-title" style="padding-top: 0;">' +
              data.data.message +
              " <br><br>Would you like to fix it or continue with this address?</h2>",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Proceed",
            cancelButtonText: "Review & Edit Address",
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
          }).then((result) => {
            if (result.isConfirmed) {
              // Create new FormData to avoid mutation issues
              const newFormData = new FormData(recipientOrderManageForm);
              newFormData.append("action", "manage_recipient_order_form");
              newFormData.append("security", oam_ajax.nonce);
              newFormData.append("invalid_address", 1);

              process_group_popup();

              return sendFormData(newFormData)
                .then((data) => {
                  if (data.success) {
                    Swal.fire({
                      title: data.data.message,
                      icon: "success",
                      showConfirmButton: false,
                      timerProgressBar: false,
                      allowOutsideClick: false,
                      allowEscapeKey: false,
                      allowEnterKey: false
                    });

                    setTimeout(() => {
                      window.location.reload();
                    }, 1500);
                  } else {
                    Swal.fire({
                      title: "Error",
                      text:
                        data.data?.message || "Failed to remove recipients.",
                      icon: "error"
                    });
                  }
                })
                .catch(() => {
                  Swal.fire({
                    title: "Error",
                    text: "An error occurred while removing recipients.",
                    icon: "error"
                  });
                });
            }
            // If canceled, do nothing or maybe focus form for editing
          });
        }

        // If success, show success and reload
        Swal.fire({
          title: data.data.message,
          icon: "success",
          timer: 3500,
          showConfirmButton: false,
          timerProgressBar: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false
        });

        window.location.reload();

        document.querySelector("[data-lity-close]")?.click();
      })
      .catch((error) => {
        Swal.fire({
          title: "Error",
          text:
            error.message || "An error occurred while processing the request.",
          icon: "error"
        });
      });
  });
}

// sendFormData helper function
function sendFormData(formData) {
  return fetch(oam_ajax.ajax_url, {
    method: "POST",
    body: formData
  }).then((res) => res.json());
}

/**
 * Edit Billing Address Start
 */

document.addEventListener("click", function (event) {
  const target = event.target;
  const isEdit = target.classList.contains("editBillingAddress");

  if (!isEdit) return;

  event.preventDefault();
  process_group_popup();

  const form = document.querySelector("#edit-billing-address-form form");
  form.reset();

  const orderID = target.getAttribute("data-order");

  fetch(oam_ajax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      action: "get_billing_details_base_order_id",
      id: orderID,
      security: oam_ajax.nonce
    })
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) {
        throw new Error(data.data.message || "Failed to get billing.");
      }

      const d = data.data;

      const fields = [
        "order_id",
        "first_name",
        "last_name",
        "address_1",
        "address_2",
        "city",
        "state",
        "zipcode",
        "phone_number",
      ];
      fields.forEach((field) => {
        const input = form.querySelector(`#${field}`);
        if (input) input.value = d[field] || "";
      });

      setTimeout(() => {
        lity(target.getAttribute("data-popup"));

        jQuery(form).find("#state").val(d.state).trigger("change");
      }, 250);

      Swal.close();
    })
    .catch((error) => {
      Swal.fire({
        title: "Error",
        text:
          error.message || "An error occurred while retrieving the recipient.",
        icon: "error"
      });
    });
});

function validateEditBillingAddressForm(form) {
  let isValid = true;
  const requiredFields = form.querySelectorAll(
    "input[required], select[required], textarea[required]"
  );

  requiredFields.forEach((field) => {
    const parentDiv = field.closest(".form-row");
    const errorMessage = parentDiv?.querySelector(".error-message");

    if (!field.value.trim()) {
      field.style.border = "1px solid red";
      if (errorMessage) {
        errorMessage.textContent =
          field.getAttribute("data-error-message") || "This field is required.";
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
const editBillingAddressForm = document.querySelector(
  "#edit-billing-address-form form"
);
if (editBillingAddressForm) {
  editBillingAddressForm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (!validateEditBillingAddressForm(this)) return;

    process_group_popup();

    const formData = new FormData(this);
    formData.append("action", "update_billing_details");
    formData.append("security", oam_ajax.nonce);

    fetch(oam_ajax.ajax_url, {
      method: "POST",
      body: formData
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          throw new Error(
            data.data.message || "Failed to update billing details."
          );
        }

        Swal.fire({
          title: data.data.message,
          icon: "success",
          timer: 3500,
          showConfirmButton: false,
          timerProgressBar: false,
          allowOutsideClick: false,
          allowEscapeKey: false,
          allowEnterKey: false
        });

        window.location.reload();

        document.querySelector("[data-lity-close]")?.click();
      })
      .catch((error) => {
        Swal.fire({
          title: "Error",
          text:
            error.message || "An error occurred while processing the request.",
          icon: "error"
        });
      });
  });
}

/**
 * Edit Billing Address END
 */

/**
 * Recipient Jar order start
 */

jQuery(function ($) {
  jQuery(document).on("click", 'input[name="table_order_type"]', function (e) {
    // e.preventDefault();
    var otype = jQuery(this).val();
    if (otype == "main_order") {
      jQuery("#customer-orders-table_wrapper").show();
      jQuery("#customer-jar-orders-table_wrapper").hide();
      jQuery("#customer-jar-orders-table").hide();

      // customer-orders-table_filter
      // customer-orders-table_info
    } else if (otype == "sub_order_order") {
      jQuery("#customer-orders-table_wrapper").hide();
      jQuery("#customer-jar-orders-table_wrapper").show();
      jQuery("#customer-jar-orders-table").show();
    }
  });

  jQuery(document).ready(function ($) {
    order_filter_main_order();
    order_filter_sub_order();
  });

  function order_filter_main_order() {
    const tabletype = $("#customer-orders-table").data("tabletype");

    var table = $("#customer-orders-table").DataTable({
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      processing: false,
      serverSide: true,
      searchDelay: 350,
      ordering: false,
      select: {
        style: "multi" // or 'single'
      },

      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        data: function (d) {
          d.action = "orthoney_customer_order_process_ajax";
          d.security = oam_ajax.nonce;
          d.custom_order_type = $("#custom-order-type-filter").val();
          d.custom_order_status = $("#custom-order-status-filter").val();
          d.custom_payment_method = $("#custom-payment-method-filter").val();
          d.table_order_type = $(
            'input[name="table_order_type"]:checked'
          ).val();
          d.selected_customer_id = $("#select-customer").val(); //
          d.selected_order_status = $("#order_status").val(); //
          d.selected_year = $("#select-year").val(); //
          const qtySlider = $("#slider-range").slider("values");
          d.tabletype = tabletype;
          d.selected_min_qty = qtySlider[0] || 1;
          d.selected_max_qty = qtySlider[1] || 1000;
          d.search_by_recipient = $(".search-recipient-name").val();

          if (
            tabletype == "organization-dashboard" ||
            tabletype == "sales-representative-dashboard"
          ) {
            const affiliate_token = $("#customer-orders-table").data(
              "affiliate_token"
            );
            d.search_by_organization = affiliate_token;
          } else {
            let organizationInput = $("input.search-by-organization").val();

            if ($.trim(organizationInput).toLowerCase() === "hfth") {
                organizationInput = "orthoney";
            }

            d.search_by_organization = organizationInput;
          }
          d.dsr_affiliate_token = $("input#dsr_affiliate_token").val();
        },
        beforeSend: function () {
          process_group_popup("Please wait while we process your request.");
        },
        complete: function () {
          setTimeout(() => {
            Swal.close();
          }, 1300);
        },
        error: function () {
          Swal.fire({
            title: "Error",
            text: "An error occurred while loading your orders.",
            icon: "error"
          });
        }
        //searching: false // disables default search box
      },
      columns: [
        {
          title: '<input type="checkbox" class="selectall-checkbox">',
          data: null,
          orderable: false,
          searchable: false,
          className: "checkbox-col",
          render: function (data, type, row) {
            return `<input type="checkbox" class="row-checkbox" value="${row.orthoney_order_id}" data-order-id="${row.orthoney_order_id}">`;
          }
        },
        { data: "jar_no" },
        { data: "order_no" },
        { data: "date" },
        { data: "billing_name", orderable: false, searchable: false },
        { data: "shipping_name", orderable: false, searchable: false },
        { data: "affiliate_code", orderable: false, searchable: false },
        { data: "total_jar", orderable: false, searchable: false },
        { data: "total_recipient", orderable: false, searchable: false },
        { data: "payment_method", orderable: false, searchable: false },
        //{ data: 'type', orderable: false, searchable: false },
        { data: 'status', orderable: false, searchable: false },
        { data: "price", orderable: false, searchable: false },
        { data: "action", orderable: false, searchable: false }
      ],
      drawCallback: function () {
        initTippy();
        $("#customer-orders-table tbody tr.sub-order-row").remove();
      },
      initComplete: function () {
        //    jQuery("#customer-orders-table_length").hide();

        const customFilter = `
              <label class="yearblock">
                Order by Year:
                <select id="select-year" class="form-control">
                <option value="">All Years</option>
                </select>
            </label>
             

              <label style="margin-left: 10px; display:none" >
             affiliate :
                 <select id="select-affiliate" class="form-control" ><option value="">Select affiliate </option></select>
             </label>
             <label class="customer-select-filter">
             Search by Customer Name:
                 <select id="select-customer" class="form-control"><option value="">Select customer</option></select>
             </label>
                  <label class="customer-select-filter">
             Search by Recipient Name:
             <input type="text" class="search-recipient-name" placeholder="Search by Recipient Name" >
             </label>
             <label class="affiliate-token-filter" style="display:none">
             Search By Org Code:
              <input id="dsr_affiliate_token" class="form-control" placeholder="Search By Org Code" >
            </label>
              <label class="customer-select-filter search-by-organization">
              Search By Org Code:
             <input type="text" class="search-by-organization" placeholder="Search By Org Code" >
             </label>
               <label>
               Order by Status:
                <select id="order_status" name="order_status" class="" tabindex="-1" aria-hidden="true">
                    <option value="all">All</option><option value="wc-processing">Processing</option>
                    <option value="wc-pending">Pending payment</option><option value="wc-on-hold">On hold</option><option value="wc-completed">Completed</option><option value="wc-cancelled">Cancelled</option><option value="wc-refunded">Refunded</option><option value="wc-failed">Failed</option></select>
             </label>
                <label style="display:none">
                    Shipping Type:
                    <select id="custom-order-type-filter" class="custom-select form-control">
                        <option value="all">All Shipping Types</option>
                        <option value="single_address">Single Address</option>
                        <option value="multiple_address">Multiple Addresses</option>
                    </select>
                </label>
                <label class="custom-order-status-filter-wrapper">
                    Order by Status:
                    <select id="custom-order-status-filter" class="custom-select form-control">
                        <option value="all">All Status</option>
                        <option value="wc-pending">Pending payment</option>
                        <option value="wc-processing">Processing</option>
                        <option value="wc-on-hold">On hold</option>
                        <option value="wc-completed">Completed</option>
                        <option value="wc-cancelled">Cancelled</option>
                        <option value="wc-refunded">Refunded</option>
                        <option value="wc-failed">Failed</option>
                        
                    </select>
                </label>
                <label class="custom-payment-method-filter-wrapper">
                    Order by Payment Methods:
                    <select id="custom-payment-method-filter" class="custom-select form-control">
                        <option value="all">All Payment Methods</option>
                        <option value="Credit card">Credit Card</option>
                        <option value="Check payments">Check Payments</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Cash on Carry">Cash on Carry</option>

                    </select>
                </label>
                <label class="rangeblock">
                <label for="amount">Quantity Range:</label>
                <input type="text" id="quantity_range" readonly="" style="border:0; color:#f6931f; font-weight:bold;">
                <div id="slider-range"></div>
                </label>

                 <div>
                    <button class="filter_btton  w-btn us-btn-style_1">Filter</Button>
                    <button class="reset_btton w-btn us-btn-style_1">Reset</Button>
                </div>
              
                <div class="custom-pdf-export-type-wrapper">

                   <label>
                    <select id="custom-pdf-export-type" class="custom-pdf-export-type form-control">
                        <option value="all">PDF Types</option>
                        <option value="2p">2P: Online & paper orders (print version)</option>
                        <option value="2e">2E: Online & paper orders (email version)</option>
                        <option value="4p">4P: Online & paper orders (print version)</option>
                        <option value="4e">4E: Online & paper orders (email version)</option>
                        <option value="5p">5p</option>
                    </select>
                </label>
                <label><div><button class="order-pdf-export w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Pdf</button></div></label></div>
                
            `;
        $("#customer-orders-table_filter").append(customFilter);

        $("#customer-orders-table_filter").append("");

        // const tableType = ``;
        // $('#customer-orders-table_length').before('<div>' + tableType + '</div>');

        jQuery("#customer-orders-table_filter input[type=search]").off();
        toggleRecipientColumn();

        $(document).on("click", 'input[name="table_order_type"]', function (e) {
          // e.preventDefault();
          toggleRecipientColumn();
          //  table.ajax.reload();
        });

        const yearSelect = document.getElementById("select-year");
        const startYear = new Date().getFullYear();
        const endYear = 2024;
        const customYear = ""; // Optional custom year to prioritize
        const defaultSelected = 2025;

        const addedYears = new Set();

        // Populate years from current down to end
        for (let year = startYear; year >= endYear; year--) {
          if (!addedYears.has(year)) {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            if (year === defaultSelected) option.selected = true;
            yearSelect.appendChild(option);
            addedYears.add(year);
          }
        }

        $(function () {
          $("#slider-range").slider({
            range: true,
            min: 1,
            max: 1000,
            values: [1, 1000],
            slide: function (event, ui) {
              $("#quantity_range").val(ui.values[0] + " - " + ui.values[1]);
              // table.draw(); // Redraw DataTable with new filter
            },
            change: function (event, ui) {
              // table.draw(); // ✅ fires once after sliding stops
            }
          });
          $("#quantity_range").val(
            $("#slider-range").slider("values", 0) +
              " - " +
              $("#slider-range").slider("values", 1)
          );
        });

        $("#select-customer").select2({
          placeholder: "Search by Customer",
          allowClear: true,
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
                action: "orthoney_get_customers_autocomplete",
                customer: params.term || "",
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

        // const tokenString = $("#customer-orders-table").data("affiliate_token"); // e.g. "A1,BSL,XYZ"

        // // Step 2: Split and create option elements
        // const tokens = tokenString
        //   ? tokenString.split(",").map((t) => t.trim())
        //   : [];

        // tokens.forEach((token) => {
        //   const option = new Option(token, token, false, false); // text, value, selected, defaultSelected
        //   $("#dsr_affiliate_token").append(option);
        // });

        // $("#dsr_affiliate_token").select2({
        //   placeholder: "Select organization...",
        //   allowClear: false
        // });

        $("#select-affiliate").select2({
          placeholder: "Select affiliate...",
          allowClear: true,
          ajax: {
            url: oam_ajax.ajax_url,
            type: "POST",
            dataType: "json",
            delay: 250,
            data: function () {
              return {
                action: "orthoney_get_used_affiliate_codes",
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

        jQuery(".selectall-checkbox").on("change", function () {
          jQuery(".row-checkbox").prop("checked", this.checked);
        });

        // 🔄 Export data trigger (new AJAX call)
        $(document).on("click", ".order-export-data", function (e) {
          e.preventDefault();

          const requestData = {
            action: "orthoney_customer_order_export_ajax",
            security: oam_ajax.nonce,
            custom_order_type: $("#custom-order-type-filter").val(),
            custom_order_status: $("#custom-order-status-filter").val(),
            table_order_type: $('input[name="table_order_type"]:checked').val(),
            search: {
              value: $("#customer-orders-table_filter input").val()
            }
          };

          process_group_popup("Generating CSV...");

          $.post(oam_ajax.ajax_url, requestData, function (response) {
            Swal.close();
            if (response.success && response.data?.url) {
              const a = document.createElement("a");
              a.href = response.data.url;
              a.download = response.data.filename;
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);
            } else {
              Swal.fire({
                title: "Export Failed",
                text:
                  response?.data?.message ||
                  "Something went wrong during export.",
                icon: "error"
              });
            }
          });
        });

        $(document).on("click", ".order-pdf-export", function (e) {
          e.preventDefault();

          let selectedValues = [];

          $(".row-checkbox:checked").each(function () {
            selectedValues.push($(this).val());
          });

          if (selectedValues.length === 0) {
            Swal.fire({
              title: "No Order Selected",
              text: "Please check at least one order before proceeding.",
              icon: "warning"
            });
            return; // Stop further execution
          }

          //  console.log(selectedValues);

          const requestData = {
            action: "orthoney_customer_order_export_pdf_ajax",
            security: oam_ajax.nonce,
            custom_order_type: $("#custom-order-type-filter").val(),
            custom_order_status: $("#custom-order-status-filter").val(),
            custom_order_pdf_type: $("#custom-pdf-export-type").val(),
            table_order_type: $('input[name="table_order_type"]:checked').val(),
            selectedValues: selectedValues,
            search: {
              value: $("#customer-orders-table_filter input").val()
            }
          };

          process_group_popup("Generating PDF...");

          $.post(oam_ajax.ajax_url, requestData, function (response) {
            Swal.close();
            if (
              response.success &&
              response.data?.url &&
              response.data.request == "download"
            ) {
              const a = document.createElement("a");
              a.href = response.data.url;
              a.download = response.data.filename;
              document.body.appendChild(a);
              a.click();
              document.body.removeChild(a);

              setTimeout(() => {
                $.post(oam_ajax.ajax_url, {
                  action: "remove_pdf_data",
                  file_url: response.data.url
                });
              }, 20000); // 5000ms = 5 seconds
            } else if (response.data?.request) {
              Swal.fire({
                title: "PDF file has been sent on email",
                text:
                  response?.data?.message ||
                  "Something went wrong during export.",
                icon: "success"
              });
              setTimeout(() => {
                $.post(oam_ajax.ajax_url, {
                  action: "remove_pdf_data",
                  file_url: response.data.url
                });
              }, 20000); // 5000ms = 5 seconds
            } else {
              Swal.fire({
                title: "Export Failed",
                text:
                  response?.data?.message ||
                  "Something went wrong during export.",
                icon: "error"
              });
            }
          });
        });

        function toggleRecipientColumn() {
          const selectedType = $(
            'input[name="table_order_type"]:checked'
          ).val();
          const filterWrapper = $(".custom-order-status-filter-wrapper");
          const recipientCountColumnIndex = 8;
          const recipientNameColumnIndex = 5;
          const jarNoColumnIndex = 1;

          if (selectedType === "sub_order_order") {
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

        jQuery(document).on(
          "change",
          "#select-customer, #order_status, #select-year",
          function (e) {
            // table.ajax.reload();
          }
        );

        jQuery(document).on("click", ".filter_btton", function (e) {
          value = jQuery(
            "#customer-orders-table_filter input[type=search]"
          ).val();

          table.search(value).draw();
          // table.ajax.reload();
        });
        jQuery(document).on("click", ".reset_btton", function (e) {
          const currentYear = new Date().getFullYear();
          $("#select-year").val(currentYear); // Set default
          $("#select-customer").val('').empty().trigger("change");
          $(".search-recipient-name").val(""); // Set default
          $(".search-by-organization").val(""); // Set default
          $("#order_status").val("all"); // Set default
          $("#custom-order-type-filter").val("all"); // Set default
          $("#custom-payment-method-filter").val("all");
          jQuery('#customer-orders-table_filter input[type="search"]').val("");
           table.search("").draw();

          const min = $("#slider-range").slider("option", "min");
          const max = $("#slider-range").slider("option", "max");
          $("#slider-range").slider("values", [min, max]);
          $("#quantity_range").val(min + " - " + max); // update display, if applicable

          table.ajax.reload();
        });
      }
    });
  }

  function order_filter_sub_order() {
    const tabletype = $("#customer-orders-table").data("tabletype");
    var table = $("#customer-jar-orders-table").DataTable({
      pageLength: 50,
      lengthMenu: [
        [10, 25, 50, 100],
        [10, 25, 50, 100]
      ],
      processing: false,
      serverSide: true,
      ordering: false,
      select: {
        style: "multi"
      },
      ajax: {
        url: oam_ajax.ajax_url,
        type: "POST",
        data: function (d) {
          d.action = "orthoney_customer_order_process_ajax";
          d.security = oam_ajax.nonce;
          d.custom_order_type = $("#custom-order-type-filter").val();
          d.custom_order_status = $("#custom-order-status-filter").val();
          d.table_order_type = "sub_order_order";
          d.selected_year = $("#jars-select-year").val();
          const qtySlider = $("#jar-slider-range").slider("values");
          d.selected_min_qty = qtySlider[0];
          d.selected_max_qty = qtySlider[1];
          d.tabletype = tabletype;
          d.selected_customer_id = $("#jar-select-customer").val();
          if (
            tabletype == "organization-dashboard" ||
            tabletype == "sales-representative-dashboard"
          ) {
            const affiliate_token = $("#customer-jar-orders-table").data(
              "affiliate_token"
            );
            d.search_by_organization = affiliate_token;
          } else {
            let organizationInput = $("input.jar-search-by-organization").val();

            if ($.trim(organizationInput).toLowerCase() === "hfth") {
                organizationInput = "orthoney";
            }
            d.search_by_organization = organizationInput;

          }
          d.jar_dsr_affiliate_token = $("input#jar_dsr_affiliate_token").val();
        },
        beforeSend: function () {
          process_group_popup("Please wait while we process your request.");
        },
        complete: function () {
          setTimeout(() => {
            Swal.close();
          }, 1300);
        },
        error: function () {
          Swal.fire({
            title: "Error",
            text: "An error occurred while loading your orders.",
            icon: "error"
          });
        }
      },
      columns: [
        { data: "jar_no" },
        { data: "date" },
        { data: "billing_name", orderable: false, searchable: false },
        { data: "affiliate_code", orderable: false, searchable: false },
        { data: "total_jar", orderable: false, searchable: false },
        { data: "jar_tracking", orderable: false, searchable: false },
        { data: "status", orderable: false, searchable: false },
        { data: "action", orderable: false, searchable: false }
      ],
      drawCallback: function () {
        initTippy();
        $("#customer-jar-orders-table tbody tr.sub-order-row").remove();
      },
      initComplete: function () {
        $("#customer-jar-orders-table_wrapper").hide();
        // $("#customer-jar-orders-table_length").hide();

        const customFilter = `
                <label class="yearblock">
                    Order by Year:
                    <select id="jars-select-year" class="form-control">
                    <option value="">All Years</option>
                    </select>
                </label>

                 <label class="affiliate-token-filter" style="display:none">
             Search By Org Code:
              <input id="jar_dsr_affiliate_token" class="form-control" placeholder="Search By Org Code">
            </label>
                <label class="customer-select-filter jar-search-by-organization">
                      Search By Org Code:
                    <input type="text" class="jar-search-by-organization" placeholder="Search By Org Code" >
                </label>
                
                <label class="customer-select-filter jar-select-customer">
                    Search by Customer Name:
                    <select id="jar-select-customer" class="form-control">
                        <option value="">Select Customer</option>
                    </select>
                </label>
                <label class="rangeblock">
                    <label for="amount">Quantity Range:</label>
                    <input type="text" id="jar_quantity_range" readonly style="border:0; color:#f6931f; font-weight:bold;">
                    <div id="jar-slider-range"></div>
                </label>
                <div>
                    <button class="jar_filter_btton  w-btn us-btn-style_1">Filter</button>
                    <button class="jar_reset_btton w-btn us-btn-style_1">Reset</button>
                </div>
            `;

        $("#customer-jar-orders-table_filter").append(customFilter);
        $("#customer-jar-orders-table_filter").append(
          '<label><div><button class="order-export-data w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Data</button></div></label>'
        );
        $("#customer-jar-orders-table_filter").append(
          '<label><div><button class="order-pdf-export w-btn us-btn-style_1" data-tippy="Download CSV file for the current data.">Export Pdf</button></div></label>'
        );

        $("#customer-jar-orders-table_length").before("<div></div>");

        jQuery("#customer-jar-orders-table_filter input[type=search]").off();
        //     $('.jar-search-by-organization').on('input', function (e) {
        //     var searchrecipient = $(this).val();

        //     if(searchrecipient == ""){
        //          table.ajax.reload(); // Reload DataTable via AJAX
        //     }

        //     if (searchrecipient.length > 2) {
        //         table.ajax.reload(); // Reload DataTable via AJAX
        //     }
        //  });

        $("#jar-select-customer").select2({
          placeholder: "Search by Customer",
          allowClear: true,
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
                action: "orthoney_get_customers_autocomplete",
                customer: params.term || "",
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

        // const tokenString = $("#customer-jar-orders-table").data(
        //   "affiliate_token"
        // ); // e.g. "A1,BSL,XYZ"

        // // Step 2: Split and create option elements
        // const tokens = tokenString
        //   ? tokenString.split(",").map((t) => t.trim())
        //   : [];

        // tokens.forEach((token) => {
        //   const option = new Option(token, token, false, false); // text, value, selected, defaultSelected
        //   $("#jar_dsr_affiliate_token").append(option);
        // });

        // $("#jar_dsr_affiliate_token").select2({
        //   placeholder: "Select organization...",
        //   allowClear: false
        // });

        // $(document).on('change', '#jar-select-customer, #jars-select-year', function () {
        //     table.ajax.reload();
        // });
        $(document).on("click", ".jar_filter_btton", function () {
          value = jQuery(
            "#customer-jar-orders-table_filter input[type=search]"
          ).val();
          table.search(value).draw();
        });

        jQuery(document).on("click", ".jar_reset_btton", function ($) {
          const currentYear = new Date().getFullYear();
          jQuery("#jars-select-year").val(currentYear); // Set default
           jQuery("#jar-select-customer").val('').empty().trigger("change");
          jQuery(".jar-search-by-organization").val(""); // Set default
          jQuery('#customer-jar-orders-table_filter input[type="search"]').val(
            ""
          );

          const min = jQuery("#jar-slider-range").slider("option", "min");
          const max = jQuery("#jar-slider-range").slider("option", "max");
          jQuery("#jar-slider-range").slider("values", [min, max]);
          jQuery("#jar_quantity_range").val(min + " - " + max); // update display, if applicable
          setTimeout(() => {
            table.search("").columns().search("").draw();
          }, 1000);
        });

        const yearSelect = document.getElementById("jars-select-year");
        const startYear = new Date().getFullYear();
        const endYear = 2024;
        const defaultSelected = 2025;
        const addedYears = new Set();

        for (let year = startYear; year >= endYear; year--) {
          if (!addedYears.has(year)) {
            const option = document.createElement("option");
            option.value = year;
            option.textContent = year;
            if (year === defaultSelected) option.selected = true;
            yearSelect.appendChild(option);
            addedYears.add(year);
          }
        }

        $(".selectall-checkbox").on("change", function () {
          $(".row-checkbox").prop("checked", this.checked);
        });

        $("#jar-slider-range").slider({
          range: true,
          min: 1,
          max: 1000,
          values: [1, 1000],
          slide: function (event, ui) {
            $("#jar_quantity_range").val(ui.values[0] + " - " + ui.values[1]);
          },
          change: function (event, ui) {
            //  table.draw();
          }
        });

        $("#jar_quantity_range").val(
          $("#jar-slider-range").slider("values", 0) +
            " - " +
            $("#jar-slider-range").slider("values", 1)
        );
      }
    });
  }
});

function jarfilter_trigger(jarOrderId, year) {
  // 1. Click the radio input
  //e.preventDefault();

  var $radio = jQuery("#sub_order_order");
  if ($radio.length && !$radio.prop("checked")) {
    $radio.prop("checked", true).trigger("change");
  }

  // 2. Set value in the DataTables search input
  var $searchInput = jQuery(
    "#customer-jar-orders-table_filter input[type=search]"
  );
  if ($searchInput.length) {
    $searchInput.val(jarOrderId);

    if (jQuery.fn.dataTable.isDataTable("#customer-jar-orders-table")) {
      jQuery("#customer-jar-orders-table").DataTable().search(jarOrderId);
    } else {
      $searchInput.trigger("input");
    }
  }
  var $yearSelect = jQuery("#jars-select-year");
  if ($yearSelect.length) {
    $yearSelect.val(year).trigger("change"); // Trigger 'change' to make sure any event listeners react
  }

  jQuery(".jar_filter_btton").click();
  jQuery("#sub_order_order").click();
}
/**
 * Recipient Order End
 */

jQuery(document).ready(function ($) {
  const recipientOrderID = getURLParam("recipient-order");
  if (recipientOrderID) {
    const recipientOrderElement = $(
      '.viewRecipientOrder[data-order="' + recipientOrderID + '"]'
    );
    if (recipientOrderElement.length) {
      const loader = document.querySelector(".multiStepForm.loader");
      loader.style.display = "none";
      recipientOrderElement.trigger("click");
    }
  }
});

jQuery(document).on("click", ".download_csv_by_order_id", function (e) {
  e.preventDefault();

  const orderid = jQuery(this).data("orderid");

  const requestData = {
    action: "orthoney_customer_order_export_by_id_ajax",
    security: oam_ajax.nonce,
    order_id: orderid
  };

  process_group_popup("Generating CSV...");

  jQuery.ajax({
    url: oam_ajax.ajax_url,
    type: "POST",
    data: requestData,
    success: function (response) {
      setTimeout(() => {
        Swal.close();
      }, 500);
      if (response.success && response.data?.url) {
        const a = document.createElement("a");
        a.href = response.data.url;
        a.download = response.data.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      } else {
        Swal.fire({
          title: "Export Failed",
          text:
            response?.data?.message || "Something went wrong during export.",
          icon: "error"
        });
      }
    },
    error: function () {
      Swal.close();
      Swal.fire({
        title: "Export Failed",
        text: "An AJAX error occurred while exporting the order.",
        icon: "error"
      });
    }
  });
});

document.addEventListener("click", function (event) {
  if (event.target.classList.contains("show-sub-order")) {
    const target = event.target;
    const orderid = target.getAttribute("data-orderid");
    const status = target.getAttribute("data-status");
    if (status == 0) {
      fetch(oam_ajax.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: new URLSearchParams({
          action: "customer_sub_order_details_ajax",
          orderid: orderid,
          security: oam_ajax.nonce
        })
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            target.setAttribute("data-status", 1);
            const tr = target.closest("tr");
            let next = tr.nextElementSibling;
          } else {
            Swal.fire({
              title: "Error",
              text:
                data.data.message ||
                "Failed to change status for organization.",
              icon: "error"
            });
          }
        })
        .catch(() => {
          Swal.fire({
            title: "Error",
            text: "An error occurred while changing status for organization.",
            icon: "error"
          });
        });
    } else {
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
      .then((response) => response.json())
      .then((data) => {
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
            text:
              data.data?.message || "Something went wrong. Please try again",
            icon: "error"
          });
        }
        setTimeout(() => {
          Swal.close();
        }, 500);
      })
      .catch(() => {
        Swal.fire({
          title: "Error",
          text: "An error occurred while updating the Incomplete Order.",
          icon: "error"
        });
      });
  }

  const dashboard_groups = document.querySelector(".groups-block #groups-data");
  if (dashboard_groups) {
    fetchOrders(1);

    document.addEventListener("click", function (event) {
      if (event.target.matches("#groups-pagination a")) {
        event.preventDefault();
        const page = event.target.getAttribute("data-page");
        if (page) {
          fetchOrders(page);
        }
      }
    });
  }
});

//Edit Sales Representative Profile
document.addEventListener("click", function (event) {
  if (event.target.id === "sales-rep-save-profile") {
    event.preventDefault();
    if (!validateForm(document.getElementById("sales-rep-profile-form")))
      return;

    const form = document.getElementById("sales-rep-profile-form");
    const formData = new FormData(form);
    formData.append("action", "update_sales_representative");
    formData.append("security", oam_ajax?.nonce || "");

    fetch(oam_ajax.ajax_url, {
      method: "POST",
      body: formData
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          Swal.fire({
            title:
              data.message ||
              "Sales Representative Profile updated successfully!",
            icon: "success",
            timer: 2000,
            showConfirmButton: false,
            timerProgressBar: true
          });
        } else {
          Swal.fire({
            title: "Error",
            text: data.message || "Something went wrong. Please try again.",
            icon: "error"
          });
        }
      })
      .catch((error) => {
        console.error("Fetch Error:", error);
        Swal.fire({
          title: "Error",
          text: "An error occurred while updating the Sales Representative profile.",
          icon: "error"
        });
      });
  }
});

//Hide default user switching button
document.addEventListener("DOMContentLoaded", function () {
  const switchBackLink = document.querySelector(
    ".woocommerce-MyAccount-navigation-link--user-switching-switch-back"
  );
  if (switchBackLink) {
    switchBackLink.remove();
  }
});

jQuery(document).ready(function ($) {
  $(document).on("click", ".confirmation_link", function (e) {
    e.preventDefault();

    const logoutUrl = $(this).attr("href");
    const message =
      "Clicking this will log you out and redirect you to the Organization Registration page.<br>Do you want to continue?";
    const loginStatus = $(this).data("loginstatus");
    if (loginStatus == 0) {
      window.location.href = logoutUrl;
      return;
    }

    Swal.fire({
      html: message,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#3085d6",
      cancelButtonColor: "#d33",
      confirmButtonText: "Yes, Proceed",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      allowOutsideClick: false,
      allowEscapeKey: false,
      allowEnterKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        process_group_popup();
        fetch(oam_ajax.ajax_url, {
          method: "POST",
          credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({ action: "oam_ajax_logout" })
        }).finally(() => {
          window.location.href = logoutUrl;
        });
      }
    });
  });
});

jQuery(document).on("click", ".org_account_statement", function (e) {
  e.preventDefault();

  const $btn = jQuery(this);
  $btn.prop("disabled", true); // Disable the button

  const requestData = {
    action: "orthoney_org_account_statement_ajax",
    security: oam_ajax.nonce,
    orgid: $btn.data("orgid")
  };

  process_group_popup("Generating CSV...");

  jQuery
    .post(oam_ajax.ajax_url, requestData)
    .done(function (response) {
      Swal.close();
      if (response.success && response.data?.url) {
        const a = document.createElement("a");
        a.href = response.data.url;
        a.download = response.data.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      } else {
        Swal.fire({
          title: "Export Failed",
          text:
            response?.data?.message || "Something went wrong during export.",
          icon: "error"
        });
      }
    })
    .fail(function () {
      Swal.close();
      Swal.fire({
        title: "Network Error",
        text: "Failed to communicate with server. Please try again.",
        icon: "error"
      });
    })
    .always(function () {
      $btn.prop("disabled", false); // Re-enable the button
    });
});

jQuery(document).ready(function ($) {
  $(document).on("click", ".activate_affiliate_account", function (e) {
    e.preventDefault();

    const user_id = $(this).data("userid");

    const requestData = {
      action: "orthoney_activate_affiliate_account_ajax",
      security: oam_ajax.nonce,
      user_id: user_id
    };

    process_group_popup();

    $.ajax({
      url: oam_ajax.ajax_url,
      type: "POST",
      data: requestData,
      success: function (response) {
        setTimeout(() => {
          Swal.close();
        }, 500);

        if (response.success) {
          Swal.fire({
            title:
              response.data?.message ||
              "Your account has been successfully activated.",
            icon: "success",
            showConfirmButton: false,
            timerProgressBar: false
          });

          setTimeout(() => {
            window.location.reload();
          }, 750);
        } else {
          Swal.fire({
            title: "Activation Failed",
            text:
              response?.data?.message ||
              "Something went wrong during activation.",
            icon: "error"
          });
        }
      },
      error: function () {
        Swal.close();
        Swal.fire({
          title: "Activation Failed",
          text: "An AJAX error occurred while activating the account.",
          icon: "error"
        });
      }
    });
  });
});

document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const errorEl = document.querySelector(
      ".season_start_end_message_box.withpopup"
    );

    if (errorEl) {
      errorEl.style.display = "block";
    }
  }, 2000);
});

(function () {
  const second = 1000,
    minute = second * 60,
    hour = minute * 60,
    day = hour * 24;

  const countdowns = document.querySelectorAll(
    ".season_start_end_message_box .countdown"
  );

  countdowns.forEach((countdown) => {
    const dataDate = countdown.getAttribute("data-date");
    const dataCurrentDate = countdown.getAttribute("data-currentdate");

    // Initial reference time from server
    let serverNow = dataCurrentDate
      ? new Date(dataCurrentDate).getTime()
      : new Date().getTime();

    if (isNaN(serverNow)) {
      console.error("Invalid data-currentdate format:", dataCurrentDate);
      return;
    }

    let elapsedTime = 0;

    function parseTargetDate() {
      if (!dataDate) return null;

      const hasTime = /\d{2}:\d{2}:\d{2}/.test(dataDate);
      let targetDate;

      if (hasTime) {
        targetDate = new Date(dataDate);
      } else {
        const parts = dataDate.split("/");
        if (parts.length === 3) {
          targetDate = new Date(dataDate);
        } else if (parts.length === 2) {
          const yyyy = new Date(serverNow).getFullYear();
          const mm = parts[0].padStart(2, "0");
          const dd = parts[1].padStart(2, "0");

          targetDate = new Date(`${mm}/${dd}/${yyyy}`);
          if (serverNow > targetDate.getTime()) {
            targetDate = new Date(`${mm}/${dd}/${yyyy + 1}`);
          }
        } else {
          return null;
        }
      }

      return isNaN(targetDate.getTime()) ? null : targetDate;
    }

    let timer;
    const targetDate = parseTargetDate();

    if (!targetDate) {
      console.error("Invalid data-date format:", dataDate);
      return;
    }

    const updateCountdown = () => {
      const now = serverNow + elapsedTime;
      const distance = targetDate.getTime() - now;

      if (distance < 0) {
        countdown.style.display = "none";
        clearInterval(timer);
        return;
      }

      const daysEl = countdown.querySelector(".days");
      const hoursEl = countdown.querySelector(".hours");
      const minutesEl = countdown.querySelector(".minutes");
      const secondsEl = countdown.querySelector(".seconds");

      if (!daysEl || !hoursEl || !minutesEl || !secondsEl) {
        console.error("Missing countdown elements");
        clearInterval(timer);
        return;
      }

      daysEl.innerText = Math.floor(distance / day);
      hoursEl.innerText = Math.floor((distance % day) / hour);
      minutesEl.innerText = Math.floor((distance % hour) / minute);
      secondsEl.innerText = Math.floor((distance % minute) / second);

      elapsedTime += 1000; // simulate time passing from initial serverNow
    };

    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
  });
})();


document.addEventListener("click", function (event) {
    const target = event.target;

    if (target.classList.contains("view_order_details")) {
        event.preventDefault();

        const org_id = target.getAttribute("data-org-id");
        const popupSelector = target.getAttribute("data-popup");

        // Show loader
        process_group_popup("Loading organization details...");

        fetch(oam_ajax.ajax_url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "get_org_details_base_id",
                org_id: org_id,
                security: oam_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {

                // Inject data into the popup content
                const popupContent = document.querySelector(popupSelector);
                if (popupContent) {
                    popupContent.querySelector('.popup-title span').textContent = data.data.org_name;
                    popupContent.querySelector('#org-website').textContent = data.data.website;
                    popupContent.querySelector('#org-phone').textContent = data.data.phone;
                    popupContent.querySelector('#org-tax-id').textContent = data.data.tax_id;
                    popupContent.querySelector('#gift_card').textContent = data.data.gift_card;
                    popupContent.querySelector('#product_price').textContent = data.data.product_price;
                    popupContent.querySelector('#org-check_payable').textContent = data.data.check_payable;
                    popupContent.querySelector('#org-check_address').textContent = data.data.address_check;
                    popupContent.querySelector('#org-check_attention').textContent = data.data.attention;
                    popupContent.querySelector('#org-check_office').textContent = data.data.check_mailed_address;
                    popupContent.querySelector('#org-full-address').textContent = data.data.full_address;
                }

                // Open popup after data is loaded
                const popup = lity(popupSelector);

                // Disable outclick close
                requestAnimationFrame(() => {
                    const lityElement = document.querySelector('.lity');
                    if (!lityElement) return;

                    lityElement.addEventListener('click', function(e) {
                        if (e.target === lityElement || e.target.classList.contains('lity-wrap')) {
                            e.stopPropagation();
                            e.preventDefault();
                        }
                    }, true);

                    // Allow close button to work
                    const closeBtn = lityElement.querySelector('.lity-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            popup.close();
                        }, { once: true });
                    }
                });

                Swal.close();

            } else {
                Swal.fire({
                    title: "Error",
                    text: data.data.message || "Failed to load organization details.",
                    icon: "error"
                });
            }
        })
        .catch(() => {
            Swal.fire({
                title: "Error",
                text: "An error occurred while processing the request.",
                icon: "error"
            });
        });
    }
});




document.addEventListener("click", function (event) {
    const target = event.target;
    if (target.classList.contains("orderchangeorg")) {
        const popup = lity(target.getAttribute("data-popup"));
        const org_details = target.getAttribute("data-organization_data");
        const wc_order_id = target.getAttribute("data-wc_order_id");
        const order_id = target.getAttribute("data-order_id");
        if(org_details != ''){
          document.querySelector('#order-switch-org-popup .org-details-div').innerHTML = 'This order will support ' + org_details;
        }
        document.querySelector('#order-switch-org-popup #wc_order_id').value  = wc_order_id;
        document.querySelector('#order-switch-org-popup #order_id').value  =  order_id;

        setTimeout(() => {
            const lityElement = document.querySelector('.lity');
            if (lityElement) {
                // Override the click handler to prevent closing on backdrop
                lityElement.addEventListener('click', function(e) {
                    // Only prevent closing if clicking on the backdrop (not the content)
                    if (e.target === lityElement || e.target.classList.contains('lity-wrap')) {
                        e.stopPropagation();
                        e.preventDefault();
                        return false;
                    }
                }, true); // Use capture phase
                
                // Ensure close button still works
                const closeBtn = lityElement.querySelector('.lity-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        popup.close();
                    });
                }
            }
        }, 50);
    }
});


const switchOrgButton = document.querySelector('#switch-org-button');

if (switchOrgButton) {
    switchOrgButton.addEventListener('click', async function () {
        const affiliateSelect = document.querySelector("#order-switch-org-popup #order-org-search");
        const selectedOption = affiliateSelect.options[affiliateSelect.selectedIndex];
        const org_token = selectedOption.getAttribute("data-token");  
     
        const org_user_id = affiliateSelect.value;
        const wc_order_id =document.querySelector("#order-switch-org-popup #wc_order_id").value;
        const order_id =document.querySelector("#order-switch-org-popup #order_id").value;
  
        console.log(org_token + ' ' + org_user_id + ' ' + wc_order_id + ' ' + order_id);
       const result = await Swal.fire({
            html: "<b>Are you sure you want to switch the organization for this order?</b><br><p style='line-height: 1.3;padding-top: 20px;'><span style='color: red;font-weight: 900;line-height: 1.1;'>Switching the organization will assign the commission for this order to the newly selected organization. This may result in a mismatch in commission calculations</span></p>",
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes",
            cancelButtonText: "No",
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            reverseButtons: true,
        });
        if (result.isConfirmed) {
            process_group_popup();

            const requestParams = new URLSearchParams({
                action: 'switch_org_to_order',
                org_token: org_token,
                org_user_id: org_user_id,
                wc_order_id: wc_order_id,
                order_id: order_id,
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
                    text: 'You have successfully switched to the selected organization.',
                    icon: "success",
                    showConfirmButton: false,
                });
                setTimeout(() => window.location.reload(), 1000);
            } else {
                Swal.fire({
                    title: "Error",
                    text: 'The order number does not match. Please try again.',
                    icon: "error",
                });
            }
        }
    });
}



jQuery(document).ready(function ($) {
    const today = moment();
    const nextWeek = moment().add(7, 'days');

    $('#date_range_picker').daterangepicker({
        startDate: today,
        endDate: nextWeek,
        drops: 'auto',
        opens: 'center',
        maxYear : moment().year(),
        minYear: 2025,
        minDate: moment('01/01/2025', 'MM/DD/YYYY'),
        showDropdowns: false,
        locale: {
            format: 'MM/DD/YYYY'
        }
    }, function (start, end, label) {
        console.log("Date range selected: " + start.format('MM/DD/YYYY') + ' to ' + end.format('MM/DD/YYYY'));
    });
});
jQuery(document).ready(function ($) {
    $('#fulfillment-report-generate_report').on('click', function (e) {
        e.preventDefault();

        const date_range = $('#date_range_picker').val();
        const sendmail = $('#fulfillment_send_mail').val();

        if (!date_range) {
            Swal.fire({
                icon: 'error',
                title: 'Missing date range',
                text: 'Please select a date range.',
            });
            return;
        }

        Swal.fire({
            title: "Generating Fulfillment Report",
            html: `
                <p>Please wait, your report is being generated.</p>
                <div style="width: 100%; background-color: #ccc; border-radius: 5px; overflow: hidden;">
                    <div id="progress-bar" style="width: 0%; height: 10px; background-color: #3085d6;"></div>
                </div>
                <p id="progress-text">0%</p>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false
        });

        // Start processing
        processFulfillmentChunk(0, date_range, sendmail);
    });

    function processFulfillmentChunk(offset, date_range, sendmail) {
        $.ajax({
            url: oam_ajax.ajax_url,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'generate_fulfillment_report',
                security: oam_ajax.nonce,
                offset: offset,
                date_range: date_range,
                sendmail: sendmail
            },
            success: function (response) {
                if (response.success) {
                    const data = response.data;
                    const progress = data.progress || 100;

                    $('#progress-bar').css('width', progress + '%');
                    $('#progress-text').text(progress + '%');

                    if (data.done) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Report Ready',
                            text: 'Your CSV files are ready and download will begin shortly.',
                            showConfirmButton: false,
                            timer: 2500
                        });

                        // Auto-download fulfillment CSV
                        if (data.fulfillment_url && data.filenames?.fulfillment) {
                            const a1 = document.createElement('a');
                            a1.href = data.fulfillment_url;
                            a1.download = data.filenames.fulfillment;
                            document.body.appendChild(a1);
                            a1.click();
                            document.body.removeChild(a1);
                        }

                        // Auto-download greetings-per-jar CSV
                        if (data.greetings_url && data.filenames?.greetings) {
                            const a2 = document.createElement('a');
                            a2.href = data.greetings_url;
                            a2.download = data.filenames.greetings;
                            document.body.appendChild(a2);
                            a2.click();
                            document.body.removeChild(a2);
                        }

                    } else {
                        // Continue with next chunk
                        setTimeout(() => {
                            processFulfillmentChunk(offset + 10, date_range, sendmail);
                        }, 300);
                    }

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.data.message || 'Something went wrong.',
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'AJAX request failed.',
                });
            }
        });
    }
});
