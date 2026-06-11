<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductsImport;

class ProductImport extends Component
{
    use WithFileUploads;

    public $file;
    public $showImportModal = false;

    protected $rules = [
        'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB Max
    ];

    public function import()
    {
        $this->validate();

        try {
            $import = new ProductsImport();
            Excel::import($import, $this->file);
            
            $this->showImportModal = false;
            $this->reset('file');
            
            $this->dispatch('products-imported', count: $import->importedCount);
            
            session()->flash('import_success', "¡Excelente! Se han importado/actualizado {$import->importedCount} productos correctamente.");
        } catch (\Exception $e) {
            session()->flash('import_error', 'Hubo un error al importar: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.product-import');
    }
}
