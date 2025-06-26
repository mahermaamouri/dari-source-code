<?php
// pages/contact.php

// 1. Initialize and Configuration
require_once __DIR__ . '/../config/init.php';

// 2. Page-specific variables
$page_title = "Contactez-nous";
$page_specific_js = "contact.js"; // Specific JS for this page

// 3. Include Header
require_once TEMPLATES_PATH . '/header.php';
?>

<div class="contact-page-container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="text-center mb-4 mb-md-5">
                <h1 class="display-5 fw-bold mb-3">Contactez-nous</h1>
                <p class="lead text-muted">
                    Une question ? Une demande spécifique ? Notre équipe est à votre écoute pour vous aider à trouver la location de vacances idéale.
                </p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <a href="tel:+21655117543" class="card text-decoration-none h-100 shadow-sm contact-info-card">
                        <div class="card-body text-center">
                            <i class="ph ph-phone-call fs-1 text-primary mb-3"></i>
                            <h5 class="card-title h6">Par Téléphone</h5>
                            <p class="card-text small text-muted">+216 55 117 543</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                     <a href="mailto:mahdi.bouafif@gmail.com" class="card text-decoration-none h-100 shadow-sm contact-info-card">
                        <div class="card-body text-center">
                            <i class="ph ph-envelope-simple fs-1 text-primary mb-3"></i>
                            <h5 class="card-title h6">Par Email</h5>
                            <p class="card-text small text-muted">mahdi.bouafif@gmail.com</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="https://www.facebook.com/dari.com.tn/" target="_blank" rel="noopener noreferrer" class="card text-decoration-none h-100 shadow-sm contact-info-card">
                        <div class="card-body text-center">
                            <i class="ph ph-facebook-logo fs-1 text-primary mb-3"></i>
                            <h5 class="card-title h6">Sur Facebook</h5>
                            <p class="card-text small text-muted">@dari</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="card shadow-lg contact-form-card">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center h4 mb-4">Envoyez-nous un message</h3>
                    <form id="contactForm" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="contactName" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="contactName" name="name" required>
                            <div class="invalid-feedback">Veuillez entrer votre nom complet.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contactEmail" class="form-label">Adresse Email</label>
                            <input type="email" class="form-control" id="contactEmail" name="email" required>
                            <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contactPhone" class="form-label">Numéro de téléphone (Optionnel)</label>
                            <input type="tel" class="form-control" id="contactPhone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="contactSubject" class="form-label">Sujet</label>
                            <input type="text" class="form-control" id="contactSubject" name="subject" required>
                             <div class="invalid-feedback">Veuillez entrer un sujet.</div>
                        </div>
                        <div class="mb-4">
                            <label for="contactMessage" class="form-label">Votre Message</label>
                            <textarea class="form-control" id="contactMessage" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback">Veuillez entrer votre message.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg submitContactBtn">
                                <i class="ph ph-paper-plane-tilt me-2"></i>Envoyer le Message
                            </button>
                        </div>
                    </form>
                    <div id="contactFormResponse" class="mt-3"></div> </div>
            </div>
        </div>
    </div>
</div>

<?php
// 4. Include Footer
require_once TEMPLATES_PATH . '/footer.php';
?>