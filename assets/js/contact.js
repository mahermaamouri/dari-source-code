// assets/js/contact.js
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contactForm');
    const formResponseDiv = document.getElementById('contactFormResponse');
    const submitButton = contactForm ? contactForm.querySelector('.submitContactBtn') : null;

    if (contactForm && submitButton) {
        contactForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            event.stopPropagation();

            if (!contactForm.checkValidity()) {
                contactForm.classList.add('was-validated');
                return;
            }
            contactForm.classList.add('was-validated');

            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...`;
            if(formResponseDiv) formResponseDiv.innerHTML = '';


            const formData = new FormData(contactForm);
            // No specific 'action' needed if api/contact.php only does one thing

            try {
                const response = await fetch('/api/contact.php', { // Adjust API path if needed
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if(formResponseDiv) {
                        formResponseDiv.innerHTML = `<div class="alert alert-success" role="alert">${result.message}</div>`;
                    }
                    contactForm.reset();
                    contactForm.classList.remove('was-validated');
                } else {
                    let errorMessage = result.message || "Une erreur s'est produite.";
                    if (result.errors && Array.isArray(result.errors)) {
                        errorMessage += "<ul>";
                        result.errors.forEach(err => {
                            errorMessage += `<li>${err}</li>`;
                        });
                        errorMessage += "</ul>";
                    }
                    if(formResponseDiv) {
                        formResponseDiv.innerHTML = `<div class="alert alert-danger" role="alert">${errorMessage}</div>`;
                    }
                }
            } catch (error) {
                console.error('Contact form submission error:', error);
                if(formResponseDiv) {
                     formResponseDiv.innerHTML = `<div class="alert alert-danger" role="alert">Impossible d'envoyer le message. Vérifiez votre connexion et réessayez.</div>`;
                }
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
});
