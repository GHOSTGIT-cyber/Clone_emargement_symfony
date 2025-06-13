<?php

namespace App\Enum;

enum UserRole: string
{
    case APPRENANT = 'apprenant';
    case FORMATEUR = 'formateur';
    case ADMIN = 'admin';
}