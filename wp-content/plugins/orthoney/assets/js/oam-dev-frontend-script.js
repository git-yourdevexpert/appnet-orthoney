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
    if (input) {  // Ensure the input element exists
        input.addEventListener("input", () => {
            console.log('Input event triggered on:', input.id);

            // Handle ZIP code
            if (input.id === 'single_order_zipcode' || input.id === 'zipcode' ) {
                let zip = input.value.replace(/\D/g, '');  // Remove non-numeric characters
                if (zip.length > 5) {
                    zip = zip.slice(0, 5) + '-' + zip.slice(5, 9);
                }
                if (zip.length > 10) {
                    zip = zip.slice(0, 10);
                }
                input.value = zip;
            }
            
            // Handle address input
            else if (input.id === 'single_order_address') {
                input.value = input.value.replace(/[^0-9, .-]/g, '');  // Only allow valid address chars
            }
            
            // Custom allow-lists for specific fields
            else if (input.id === 'full_name' || input.id === 'company_name') {
                input.value = input.value.replace(/[^0-9a-zA-Z '&.,()-]/g, '');  // Allow names, company names
            }
            
            else if (input.id === 'address_1' || input.id === 'address_2') {
                input.value = input.value.replace(/[^0-9a-zA-Z ,.#'&/()-]/g, '');  // Allow address and hash
            }
            
            else if (input.id === 'greeting') {
                // input.value = input.value.replace(/[^\n0-9a-zA-Z ,.#'&/()-]/g , '');
            }
            else if (input.id === 'city') {
                input.value = input.value.replace(/[^0-9a-zA-Z \-'.`()]/g, '');  // Allow city names
            } else {
                input.value = input.value.replace(/[^a-zA-Z0-9, .]/g, '');  // General validation
            }

        });
    }
});

    
    document.querySelectorAll('#product_price').forEach(input => {
        input.addEventListener("input", () => {
            // Remove invalid characters (only digits and a single dot allowed)
            let value = input.value.replace(/[^0-9.]/g, '');

            // Ensure only one dot is present
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }

            // Limit to 2 decimal places
            if (parts.length === 2) {
                parts[1] = parts[1].substring(0, 2); // Keep only 2 digits after dot
                value = parts[0] + '.' + parts[1];
            }

            input.value = value;
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