<?php

use App\Models\User;
use App\Enums\UserRole;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $roleFilter = '';

    // Semi-CRUD properties
    public $showEditModal = false;
    public $editingUserId = null;
    public $editingName = '';
    public $editingPhone = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedRoleFilter()
    {
        $this->resetPage();
    }

    public function updateRole($userId, $newRole)
    {
        $user = User::findOrFail($userId);
        
        // Prevent changing own role
        if ($user->id === auth()->id()) {
            session()->flash('error', 'No puedes cambiar tu propio rol.');
            return;
        }

        $user->role = UserRole::from($newRole);
        $user->save();

        session()->flash('message', 'Rol actualizado exitosamente.');
    }

    public function toggleBan($userId)
    {
        $user = User::findOrFail($userId);
        
        if ($user->id === auth()->id()) {
            session()->flash('error', 'No puedes bloquearte a ti mismo.');
            return;
        }

        $user->is_banned = !$user->is_banned;
        $user->save();

        $status = $user->is_banned ? 'bloqueado' : 'desbloqueado';
        session()->flash('message', "Usuario {$status} exitosamente.");
    }

    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->editingUserId = $user->id;
        $this->editingName = $user->name;
        $this->editingPhone = $user->phone ?? '';
        $this->showEditModal = true;
    }

    public function saveUser()
    {
        $this->validate([
            'editingName' => 'required|string|max:255',
            'editingPhone' => 'nullable|string|max:50',
        ]);

        $user = User::findOrFail($this->editingUserId);
        $user->name = $this->editingName;
        $user->phone = $this->editingPhone;
        $user->save();

        $this->showEditModal = false;
        session()->flash('message', 'Usuario actualizado exitosamente.');
    }

    public function with(): array
    {
        $query = User::query()->withCount('orders');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        return [
            'users' => $query->latest()->paginate(20),
            'roles' => UserRole::cases(),
        ];
    }
}
?>

<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Gestión de Usuarios</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Administra los clientes y sus roles en la plataforma.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre o email..." class="w-full py-2.5 px-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
        </div>
        <div class="w-full sm:w-64">
            <select wire:model.live="roleFilter" class="w-full py-2.5 px-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                <option value="">Todos los Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Registro</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Órdenes</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        {{ $user->name }}
                                        @if($user->is_banned)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                Baneado
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">📞 {{ $user->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->created_at->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-700 dark:text-gray-300">
                            {{ $user->orders_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm flex items-center gap-2">
                            <select wire:change="updateRole({{ $user->id }}, $event.target.value)" 
                                    class="py-1 px-3 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-300 focus:ring-[var(--color-primary)] {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    @if($user->id === auth()->id()) disabled @endif>
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}" @if(optional($user->role)->value === $role->value) selected @endif>
                                        {{ $role->label() }}
                                    </option>
                                @endforeach
                            </select>

                            <button wire:click="editUser({{ $user->id }})" title="Editar Usuario" class="text-gray-500 hover:text-[var(--color-primary)] transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>

                            @if($user->id !== auth()->id())
                                <button wire:click="toggleBan({{ $user->id }})" wire:confirm="¿Seguro que quieres {{ $user->is_banned ? 'desbloquear' : 'bloquear' }} a este usuario?" title="{{ $user->is_banned ? 'Desbloquear' : 'Bloquear (Lista Negra)' }}" class="{{ $user->is_banned ? 'text-green-600 hover:text-green-700' : 'text-red-500 hover:text-red-700' }} transition-colors p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                    @if($user->is_banned)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    @endif
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Edit User Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="$set('showEditModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form wire:submit.prevent="saveUser">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-black text-gray-900 dark:text-white mb-4" id="modal-title">
                            Editar Usuario
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="editingName" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Nombre</label>
                                <input type="text" wire:model="editingName" id="editingName" class="mt-1 w-full py-2 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                                @error('editingName') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label for="editingPhone" class="block text-sm font-bold text-gray-700 dark:text-gray-300">Teléfono (Opcional)</label>
                                <input type="text" wire:model="editingPhone" id="editingPhone" class="mt-1 w-full py-2 px-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:ring-2 focus:ring-[var(--color-primary)]">
                                @error('editingPhone') <span class="text-xs text-red-500 font-bold mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-[var(--color-primary)] text-base font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Guardar
                        </button>
                        <button type="button" wire:click="$set('showEditModal', false)" class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--color-primary)] sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
