<?php
namespace App\Enum;

enum Statut: string
{
    case CREATED = "created"; 
    case PENDING = "pending"; 
    case ACTIVE = "active"; 
    case EXPIRED = "expired"; 
    case CANCELLED = "cancelled";
}