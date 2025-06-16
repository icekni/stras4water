<?php
namespace App\Enum;

enum DonationStatus: string
{
    case CREATED = "created"; // Don initialisé
    case PAID = "paid"; // Paiement validé, en attente de génération de recu fiscal
    case COMPLETED = "completed"; // Recu fiscal généré et envoyé si demandé, sinon paiement validé
    case CANCELLED = "cancelled";
}