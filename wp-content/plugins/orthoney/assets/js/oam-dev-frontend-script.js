document.addEventListener('DOMContentLoaded', function () {
    
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