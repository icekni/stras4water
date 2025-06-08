<?php
namespace App\Enum;

enum DonationStatus: string
{
    case CREATED = "created";
    case PENDING = "pending";
    case PAID = "paid";
    case CANCELLED = "cancelled";
}