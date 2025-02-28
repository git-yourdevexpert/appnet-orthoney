const singleOrderButton = document.querySelectorAll('.order-format-section .single-order');
if(singleOrderButton.length > 0){
    singleOrderButton.forEach(button => {
        button.addEventListener('click', function(event) {
            const target = event.target; 
            
            const parentDiv = target.closest('.order-format-section');
            const wrapper = parentDiv.querySelector('.single-order-wrapper');
            const recipientWrapper = parentDiv.querySelector('.recipient-order-wrapper');
           
            if (wrapper) {
                wrapper.style.display = "block";
                recipientWrapper.style.display = "none";
            }
        });
        
    });
}

const recipientOrderButton = document.querySelectorAll('.order-format-section .order-with-recipient');
if(recipientOrderButton.length > 0){
    recipientOrderButton.forEach(button => {
        button.addEventListener('click', function(event) {
            const target = event.target; 
            
            const parentDiv = target.closest('.order-format-section');
            const wrapper = parentDiv.querySelector('.single-order-wrapper');
            const recipientWrapper = parentDiv.querySelector('.recipient-order-wrapper');
           
            if (wrapper) {
                
                wrapper.style.display = "none";
                recipientWrapper.style.display = "block";
            }
        });
        
    });
}


const reOrderButton = document.querySelectorAll('.recipient-order-wrapper .re-order');
if(reOrderButton.length > 0){
    reOrderButton.forEach(button => {
        button.addEventListener('click', function(event) {
            const target = event.target; 
            
            const parentDiv = target.closest('.recipient-order-wrapper');
            const reOrderWrapper = parentDiv.querySelector('.re-order-wrapper');
            const uploadCSVWrapper = parentDiv.querySelector('.upload-csv-wrapper');
            const existingRecipientsWrapper = parentDiv.querySelector('.existing-recipients-wrapper');
           
            if (reOrderWrapper) {
                
                reOrderWrapper.style.display = "block";
                uploadCSVWrapper.style.display = "none";
                existingRecipientsWrapper.style.display = "none";
            }
        });
        
    });
}

const uploadCSVButton = document.querySelectorAll('.recipient-order-wrapper .upload-csv');
if(uploadCSVButton.length > 0){
    uploadCSVButton.forEach(button => {
        button.addEventListener('click', function(event) {
            const target = event.target; 
            
            const parentDiv = target.closest('.recipient-order-wrapper');
            const reOrderWrapper = parentDiv.querySelector('.re-order-wrapper');
            const uploadCSVWrapper = parentDiv.querySelector('.upload-csv-wrapper');
            const existingRecipientsWrapper = parentDiv.querySelector('.existing-recipients-wrapper');
           
            if (reOrderWrapper) {
                
                reOrderWrapper.style.display = "none";
                uploadCSVWrapper.style.display = "block";
                existingRecipientsWrapper.style.display = "none";
            }
        });
        
    });
}

const existingCSVButton = document.querySelectorAll('.recipient-order-wrapper .existing-recipients');
if(existingCSVButton.length > 0){
    existingCSVButton.forEach(button => {
        button.addEventListener('click', function(event) {
            const target = event.target; 
            
            const parentDiv = target.closest('.recipient-order-wrapper');
            const reOrderWrapper = parentDiv.querySelector('.re-order-wrapper');
            const uploadCSVWrapper = parentDiv.querySelector('.upload-csv-wrapper');
            const existingRecipientsWrapper = parentDiv.querySelector('.existing-recipients-wrapper');
           
            if (reOrderWrapper) {
                
                reOrderWrapper.style.display = "none";
                uploadCSVWrapper.style.display = "none";
                existingRecipientsWrapper.style.display = "block";
            }
        });
        
    });
}