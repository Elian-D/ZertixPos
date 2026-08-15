<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-4" 
         x-data="journalEntryForm()" 
         x-init="init()">
        
        <form action="{{ route('finance.journal_entries.store') }}" method="POST"
            class="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-100">
            @csrf

            <x-ui.toasts />
            
            <x-form-header
                title="Nuevo Asiento Contable"
                subtitle="Registre transacciones financieras asegurando la partida doble."
                :back-route="route('finance.journal_entries.index')" />

            <div class="p-8 space-y-8">
                
                {{-- SECCIÓN 1: CABECERA --}}
                <section class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-gray-50/50 p-6 rounded-xl border border-gray-100">
                    <div class="md:col-span-1">
                        <x-input-label value="Fecha Contable" />
                        <x-text-input type="date" name="entry_date" class="w-full mt-1" 
                            value="{{ date('Y-m-d') }}" required />
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label value="Referencia / Documento" />
                        <x-text-input name="reference" class="w-full mt-1" placeholder="Ej: CH-001 o FAC-502" />
                    </div>
                    <div class="md:col-span-1">
                        <x-input-label value="Estado Inicial" />
                        <div class="mt-2 text-sm font-bold text-amber-600 uppercase tracking-widest">
                            <span class="px-3 py-1 bg-amber-100 rounded-lg">Borrador</span>
                        </div>
                    </div>
                    <div class="md:col-span-3">
                        <x-input-label value="Concepto o Glosa" />
                        <textarea name="description" rows="2" class="w-full mt-1 rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 text-sm" 
                            placeholder="Describa el motivo de la transacción..." required></textarea>
                    </div>
                </section>

                {{-- SECCIÓN 2: DETALLE (PARTIDA DOBLE) --}}
                <section>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 uppercase text-xs tracking-wider flex items-center gap-2">
                            <span class="w-6 h-6 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px]">2</span>
                            Movimientos del Asiento
                        </h3>
                        <button type="button" @click="addLine()" 
                            class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-600 hover:text-white transition-all font-bold">
                            + Añadir Línea
                        </button>
                    </div>

                    <div class="border rounded-xl overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 w-1/2">Cuenta Contable</th>
                                    <th class="px-4 py-3">Débito</th>
                                    <th class="px-4 py-3">Crédito</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(line, index) in lines" :key="index">
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="p-2">
                                            <select :name="`items[${index}][accounting_account_id]`" 
                                                    class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500" 
                                                    @change="calculateTotals()"
                                                    x-model="line.accounting_account_id" 
                                                    required 
                                                    class="w-full border-gray-200 rounded-lg text-sm focus:ring-indigo-500" required>
                                                <option value="">Seleccione cuenta...</option>
                                                @foreach($accounts as $acc)
                                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="p-2">
                                            <input type="number" :name="`items[${index}][debit]`" x-model.number="line.debit"
                                                step="0.01" min="0" @input="calculateTotals()"
                                                class="w-full border-gray-200 rounded-lg text-sm text-right font-mono focus:ring-emerald-500" placeholder="0.00">
                                        </td>
                                        <td class="p-2">
                                            <input type="number" :name="`items[${index}][credit]`" x-model.number="line.credit"
                                                step="0.01" min="0" @input="calculateTotals()"
                                                class="w-full border-gray-200 rounded-lg text-sm text-right font-mono focus:ring-indigo-500" placeholder="0.00">
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" @click="removeLine(index)" x-show="lines.length > 2"
                                                class="text-red-400 hover:text-red-600 transition">
                                                <x-heroicon-s-trash class="w-5 h-5"/>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50 border-t-2 border-gray-200 font-bold">
                                <tr>
                                    <td class="px-4 py-4 text-right text-gray-500 uppercase text-[10px]">Totales y Balance</td>
                                    <td class="px-4 py-4 text-right text-lg font-mono" :class="isBalanced ? 'text-emerald-600' : 'text-red-600'" x-text="formatMoney(totalDebit)"></td>
                                    <td class="px-4 py-4 text-right text-lg font-mono" :class="isBalanced ? 'text-emerald-600' : 'text-red-600'" x-text="formatMoney(totalCredit)"></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Alerta de Desbalance --}}
                    <div x-show="!isBalanced" x-transition class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg flex items-center gap-3 text-red-700 text-xs">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5" />
                        <span>El asiento está desbalanceado por una diferencia de <strong x-text="formatMoney(Math.abs(totalDebit - totalCredit))"></strong>. Débito y Crédito deben ser iguales.</span>
                    </div>

                    <div x-show="hasDuplicates" x-transition class="mt-2 p-3 bg-amber-50 border border-amber-100 rounded-lg flex items-center gap-3 text-amber-700 text-xs">
                        <x-heroicon-s-exclamation-circle class="w-5 h-5" />
                        <span>Has seleccionado la misma cuenta contable en varias líneas. Por favor, unifica los montos.</span>
                    </div>
                </section>
            </div>

            <div class="p-6 bg-gray-50 flex justify-between items-center border-t">
                <p class="text-xs text-gray-400 italic">Recuerde que los asientos en 'Borrador' pueden editarse antes de ser asentados definitivamente.</p>
                <div class="flex gap-3">
                    <a href="{{ route('finance.journal_entries.index') }}" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 transition">Cancelar</a>
                    <x-primary-button class="bg-indigo-600 shadow-lg px-8" ::disabled="!isBalanced || totalDebit <= 0 || hasDuplicates">
                        Guardar Asiento
                    </x-primary-button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function journalEntryForm() {
            return {
                lines: [
                    { accounting_account_id: '', debit: 0, credit: 0 },
                    { accounting_account_id: '', debit: 0, credit: 0 }
                ],
                totalDebit: 0,
                totalCredit: 0,
                isBalanced: true,
                hasDuplicates: false,

                init() {
                    this.calculateTotals();
                },

                addLine() {
                    this.lines.push({ accounting_account_id: '', debit: 0, credit: 0 });
                },

                removeLine(index) {
                    this.lines.splice(index, 1);
                    this.calculateTotals();
                },

                calculateTotals() {
                    // 1. Calcular Totales
                    this.totalDebit = this.lines.reduce((sum, line) => sum + (parseFloat(line.debit) || 0), 0);
                    this.totalCredit = this.lines.reduce((sum, line) => sum + (parseFloat(line.credit) || 0), 0);
                    
                    // 2. Validar Balance (tolerancia de centavos)
                    this.isBalanced = Math.abs(this.totalDebit - this.totalCredit) < 0.01;

                    // 3. Validar Duplicados
                    const ids = this.lines
                        .map(l => l.accounting_account_id)
                        .filter(id => id !== '' && id !== null);
                    
                    this.hasDuplicates = new Set(ids).size !== ids.length;
                },

                formatMoney(amount) {
                    return new Intl.NumberFormat('en-US', { 
                        minimumFractionDigits: 2, 
                        maximumFractionDigits: 2 
                    }).format(amount);
                }
            }
        }
    </script>
</x-app-layout>