<?php
namespace App\Enum;

enum MoyenPaiement: string
{
    case CASH = 'cash';
    case CARTE = 'carte';
    case VIREMENT = 'virement';
    case CHEQUE = 'cheque';
}