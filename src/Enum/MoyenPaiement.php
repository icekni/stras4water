<?php
namespace App\Enum;

enum MoyenPaiement: string
{
    case CASH = 'cash';
    case STRIPE = 'stripe';
    case SUMUP = 'sumup';
    case VIREMENT = 'virement';
    case CHEQUE = 'cheque';
    case BENEVOLE = 'benevole';
}