jQuery("#multiStepForm .search-icon").each(function () {
    jQuery(this).on("click", function () {
      const tableContainer = jQuery(this).closest(".table-data");
      const dataTableFilter = tableContainer.find(".dataTables_filter");
      const isOpen = dataTableFilter.hasClass("open");
  
      // Close all filters and remove all 'close' icons
      jQuery(".dataTables_filter").removeClass("open");
      jQuery("#multiStepForm .search-icon").removeClass("close");
  
      // Open only if it was previously closed
      if (!isOpen) {
        dataTableFilter.addClass("open");
        jQuery(this).addClass("close");
      }
    });
});

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll(`
        #multiStepForm textarea, 
        #multiStepForm input[type='text'], 
        #multiStepForm input[type='search'], 
        #recipient-manage-form textarea, 
        #recipient-manage-form input[type='text'], 
        #recipient-manage-form input[type='search'], 
        #recipient-manage-order-form textarea, 
        #recipient-manage-order-form input[type='text'], 
        #recipient-manage-order-form input[type='search'], 
        input[name='csv_name'], 
        .swal2-input,
        #single_order_zipcode,
        #single_order_address
    `).forEach(input => {
        input.addEventListener("input", () => {
            if (input.id === 'single_order_zipcode') {
                // Remove all non-numeric characters
                let zip = input.value.replace(/\D/g, '');
    
                // Format ZIP code
                if (zip.length > 5) {
                    zip = zip.slice(0, 5) + '-' + zip.slice(5, 9);
                }
    
                // Limit ZIP code length
                if (zip.length > 10) {
                    zip = zip.slice(0, 10);
                }
    
                input.value = zip;
            } else if (input.id === 'single_order_address') {
                // Address: Allow only letters, numbers, commas, dots, and hyphens
                input.value = input.value.replace(/[^0-9, .-]/g, '');
            } else {
                // General validation for other fields
                input.value = input.value.replace(/[^a-zA-Z0-9, .]/g, '');
            }
        });
    });
    
    

    //append new passwordless login button beside social icon
    const socialNetworksDiv = document.querySelector('.user-registration-social-connect-networks');
    const passwordlessLoginDiv = document.querySelector('.user-registration-passwordless-login a');

    if (socialNetworksDiv && passwordlessLoginDiv) {
        const loginLink = document.createElement('a');
        const passwordlessUrl = passwordlessLoginDiv.getAttribute('href'); // Get URL from data-url attribute
        loginLink.href = passwordlessUrl;
        loginLink.textContent = 'Passwordless Login';
        loginLink.classList.add('custom-passwordless-link');
        socialNetworksDiv.appendChild(loginLink);
    }
});