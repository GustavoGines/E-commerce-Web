<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pendiente';
    case Paid = 'pagado';
    case Completed = 'completado';
    case Cancelled = 'cancelado';
    
    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pendiente',
            self::Paid => 'Pagado',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::Pending => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            self::Paid => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            self::Completed => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            self::Cancelled => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        };
    }
}
