<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Mayorista = 'mayorista';
    case Minorista = 'minorista';
    case User = 'user'; // Compatibilidad legacy
    
    public function label(): string
    {
        return match($this) {
            self::Admin => 'Administrador',
            self::Mayorista => 'Mayorista (Manual)',
            self::Minorista => 'Cliente / Usuario',
            self::User => 'Cliente / Usuario',
        };
    }
}
