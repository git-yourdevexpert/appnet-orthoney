document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll(`
        #multiStepForm textarea, 
        #multiStepForm input[type='text'], 
        #multiStepForm input[type='search'], 
        #recipient-manage-form textarea, 
        #recipient-manage-form input[type='text'], 
        #recipient-manage-form input[type='search'], 
        input[name='csv_name'], 
        .swal2-input
    `).forEach(input => {
        input.addEventListener("input", () => {
            input.value = input.value.replace(/[^a-zA-Z0-9, .]/g, '');
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