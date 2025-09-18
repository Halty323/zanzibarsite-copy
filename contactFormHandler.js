document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');

    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission

        // Collect form data
        const formData = new FormData(form);

        // Example: Log form data to console
        for (let [key, value] of formData.entries()) {
            console.log(key, value);
        }

        // TODO: Implement actual form submission logic, e.g., AJAX request to server

        // Reset the form after submission
        form.reset();
    });
});
