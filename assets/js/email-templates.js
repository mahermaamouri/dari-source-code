export function getProcessingEmailTemplate(reservation) {
    return `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;">
            <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="color: #3B82F6; font-size: 24px; font-weight: bold;">
                        <span style="margin-right: 10px;">🏠</span>
                        Dari
                    </div>
                </div>
                
                <div style="color: #1F2937; margin-bottom: 20px;">
                    <p style="margin: 0 0 15px 0; font-size: 18px; font-weight: bold;">Bonjour ${reservation.name},</p>
                    <p style="margin: 0 0 20px 0; line-height: 1.5;">Nous avons bien reçu votre demande de réservation. Notre équipe va traiter votre demande et vous contactera par téléphone dans les plus brefs délais pour confirmer votre réservation.</p>
                </div>

                <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="font-weight: bold; color: #1F2937; margin-bottom: 15px;">Détails de la réservation :</div>
                    
                    <div style="margin-bottom: 10px;">
                        <div style="color: #6B7280; font-size: 14px;">Propriété</div>
                        <div style="color: #1F2937;">${reservation.houseTitle}</div>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <div style="color: #6B7280; font-size: 14px;">Dates</div>
                        <div style="color: #1F2937;">${reservation.startDate} - ${reservation.endDate}</div>
                    </div>
                    
                    <div style="margin-bottom: 10px;">
                        <div style="color: #6B7280; font-size: 14px;">Durée</div>
                        <div style="color: #1F2937;">${reservation.nights} nuits</div>
                    </div>
                    
                    <div style="margin-bottom: 0;">
                        <div style="color: #6B7280; font-size: 14px;">Prix total</div>
                        <div style="color: #1F2937; font-weight: bold;">${reservation.totalPrice} TND</div>
                    </div>
                </div>

                <div style="color: #6B7280; font-size: 14px; line-height: 1.5;">
                    <p style="margin: 0 0 10px 0;">Nous vous contacterons au ${reservation.phone} pour finaliser votre réservation.</p>
                    <p style="margin: 0;">Si vous avez des questions, n'hésitez pas à nous contacter à contact@dari.tn</p>
                </div>
            </div>
        </div>
    `;
}

export function getConfirmationEmailTemplate(reservation) {
    return `
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9fafb;">
            <div style="background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="color: #3B82F6; font-size: 24px; font-weight: bold;">
                        <span style="margin-right: 10px;">🏠</span>
                        Dari
                    </div>
                    <div style="color: #10B981; font-size: 20px; margin-top: 10px;">
                        Réservation Confirmée
                    </div>
                </div>
                
                <div style="color: #1F2937; margin-bottom: 20px;">
                    <p style="margin: 0 0 15px 0; font-size: 18px; font-weight: bold;">Bonjour ${reservation.name},</p>
                    <p style="margin: 0 0 20px 0; line-height: 1.5;">Votre réservation a été confirmée ! Voici les détails de votre séjour :</p>
                </div>

                <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="margin-bottom: 15px;">
                        <div style="color: #6B7280; font-size: 14px;">Propriété</div>
                        <div style="color: #1F2937; font-weight: bold;">${reservation.houseTitle}</div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="color: #6B7280; font-size: 14px;">Adresse</div>
                        <div style="color: #1F2937;">${reservation.houseAddress}</div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div style="color: #6B7280; font-size: 14px;">Dates du séjour</div>
                        <div style="color: #1F2937;">${reservation.startDate} - ${reservation.endDate}</div>
                    </div>
                </div>

                <div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="font-weight: bold; color: #1F2937; margin-bottom: 15px;">Paiement :</div>
                    
                    <div style="margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #6B7280;">Prix total</span>
                            <span style="color: #1F2937;">${reservation.totalPrice} TND</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #6B7280;">Acompte payé</span>
                            <span style="color: #10B981;">${reservation.advancePayment} TND</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                            <span style="color: #6B7280;">Reste à payer</span>
                            <span style="color: #1F2937; font-weight: bold;">${reservation.remainingPayment} TND</span>
                        </div>
                    </div>
                </div>

                <div style="background-color: #fff7ed; border: 1px solid #fdba74; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="color: #c2410c; font-weight: bold; margin-bottom: 5px;">Important :</div>
                    <div style="color: #9a3412; font-size: 14px;">Le reste du paiement devra être effectué à votre arrivée.</div>
                </div>

                <div style="color: #6B7280; font-size: 14px; line-height: 1.5; text-align: center;">
                    <p style="margin: 0 0 10px 0;">Pour toute question, contactez-nous :</p>
                    <p style="margin: 0;">Email : contact@dari.tn | Téléphone : +216 XX XXX XXX</p>
                </div>
            </div>
        </div>
    `;
}