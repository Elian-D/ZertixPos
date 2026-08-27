<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ConfirmDeletionModal extends Component
{
    public function __construct(
        public string $id,          // ID del registro
        public string $title,       // Título del modal
        public string $itemName,    // Nombre del objeto (ej: "Juan Pérez")
        public ?string $route = null,      // Ruta de eliminación (form POST clásico)
        public string $type = 'registro', // Tipo (ej: "el cliente", "el equipo")
        public string $method = 'DELETE',  // Por defecto DELETE
        public ?string $description = null, // <-- Nueva propiedad opcional
        // Alternativa a :route para componentes Livewire (ej. Papelera del
        // motor DataTable, REQ-0.7): nombre + argumentos de la acción del
        // componente, ej. "forceDelete(5)" — el botón de confirmar usa
        // wire:click en vez de <form method="POST">. Pasa uno de los dos,
        // route o wireConfirm, nunca ambos.
        public ?string $wireConfirm = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.ui.confirm-deletion-modal');
    }

    /**
     * Formatea el tipo para mostrarlo en el texto del cuerpo
     */
    public function getFormattedType(): string
    {
        return ucfirst(mb_strtolower($this->type));
    }
}