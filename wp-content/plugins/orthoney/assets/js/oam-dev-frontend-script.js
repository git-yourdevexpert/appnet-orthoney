document.addEventListener('DOMContentLoaded', function () {
    const subOrdersCreation = document.querySelector('#thank-you-sub-orders-creation');

    if (subOrdersCreation) {
        const orderId = subOrdersCreation.getAttribute('data-order_id');
        const groupId = subOrdersCreation.getAttribute('data-group_id');

        process_group_popup('Please wait while the sub-order process is being completed.');

        setTimeout(() => {
            let currentChunk = 0; // Initialize chunk tracking
            let chunkSize = 2; // Set chunk size

            function uploadChunk() {
                let params = new URLSearchParams();
                params.append("action", "orthoney_thank-you-sub-orders-creation_ajax");
                params.append("security", oam_ajax.nonce);
                params.append("order_id", orderId);
                params.append("group_id", groupId);
                params.append("current_chunk", currentChunk);
                params.append("chunk_size", chunkSize);

                fetch(oam_ajax.ajax_url, {
                    method: "POST",
                    body: params
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (currentChunk === 0) {
                            Swal.fire({
                                title: "Please wait, the recipient's order is being created.",
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

                        let progress = data.data.progress;
                        document.getElementById("progress-bar").style.width = progress + "%";
                        document.getElementById("progress-text").innerText = progress + "%";

                        if (!data.data.finished) {
                            currentChunk = data.data.next_chunk; // Move to next chunk
                            uploadChunk();
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Orders has been successfully completed!',
                                showConfirmButton: false,
                                timer: 2500
                            });
                            // setTimeout(() => {
                            //     window.location.reload();
                            // }, 1000);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: data.data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing the request.'
                    });
                    console.error("AJAX Error:", error);
                });
            }

            uploadChunk(); // Start processing
        }, 500); // Delay to ensure popup is shown
    }
});
