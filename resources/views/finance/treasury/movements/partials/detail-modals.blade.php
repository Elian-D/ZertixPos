{{-- Detalle de cada movimiento de Tesorería (v1.5.0 REQ-3.5), mismo papel que el detalle
     de Movimientos de Stock. Desde aquí se anula o se corrige un movimiento manual, con la
     vista previa del saldo en que quedará la cuenta.
     Recibe $movements (con withIndexRelations()). Cada form manda un `_form` oculto para que
     un error de validación reabra el mismo modal en la misma acción. --}}
@use('App\Models\Accounting\FinancialMovement')
@php
    $currency = config('regional.currency_symbol');
    $money = fn ($v) => $currency.number_format((float) $v, 2);
    $canMove = auth()->user()->can('financial_accounts.move');
    $failed = $errors->hasAny(['void_reason', 'correct_amount', 'correct_reason']) ? old('_form') : null;
@endphp

@foreach($movements as $m)
    @php
        $name = 'view-movement-'.$m->id;
        $editable = $canMove && $m->isEditable() && $m->account?->is_active;
        $isTransfer = $m->origin === FinancialMovement::ORIGIN_TRANSFER;
        $doc = $m->document;
        $reopen = $failed && str_starts_with($failed, $name.':') ? substr($failed, strlen($name) + 1) : null;
    @endphp

    <x-modal :name="$name" maxWidth="lg" :show="(bool) $reopen">
        <div class="p-6" x-data="{
                mode: @js($reopen),
                balance: {{ (float) ($m->account->current_balance ?? 0) }},
                signed: {{ $m->signed_amount }},
                inbound: @js($m->isIn()),
                amount: @js((string) ($reopen === 'correct' ? old('correct_amount') : '')),
                get afterVoid() { return Math.round((this.balance - this.signed) * 100) / 100 },
                get afterCorrect() {
                    const a = parseFloat(this.amount);
                    return isNaN(a) ? null : Math.round((this.afterVoid + (this.inbound ? a : -a)) * 100) / 100;
                },
                fmt(v) { return (v < 0 ? '−' : '') + @js($currency) + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
            }">

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <x-ui.badge :variant="$m->origin_variant" size="sm" :dot="false">{{ $m->origin_label }}</x-ui.badge>
                        @if($m->isReversed())
                            <x-ui.badge variant="error" size="sm" :dot="false">Anulado</x-ui.badge>
                        @endif
                    </div>
                    <h3 class="mt-2 text-lg font-semibold text-gray-900 truncate">{{ $m->account->name ?? '—' }}</h3>
                    <p class="text-xs text-gray-500">{{ $m->movement_date->format('d/m/Y') }} · registrado {{ $m->created_at->format('d/m/Y h:i A') }}</p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" x-on:click="$dispatch('close')" aria-label="Cerrar">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Saldo antes → monto → saldo después --}}
            <div class="mt-5 grid grid-cols-3 items-center rounded-xl bg-gray-50 px-4 py-4 text-center">
                <div>
                    <span class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Saldo antes</span>
                    <span class="text-sm font-semibold text-gray-600 tabular-nums">{{ $money($m->balance_before) }}</span>
                </div>
                <div>
                    <span @class(['inline-block rounded-md px-2 py-1 text-sm font-bold tabular-nums', 'bg-state-success/10 text-state-success' => $m->isIn(), 'bg-state-error/10 text-state-error' => ! $m->isIn()])>
                        {{ $m->isIn() ? '+' : '−' }}{{ $money($m->amount) }}
                    </span>
                </div>
                <div>
                    <span class="block text-[11px] font-semibold uppercase tracking-wide text-gray-400">Saldo después</span>
                    <span class="text-base font-bold text-gray-900 tabular-nums">{{ $money($m->balance_after) }}</span>
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                @if($m->detail)
                    <div>
                        <dt class="text-xs font-semibold text-gray-500">Detalle</dt>
                        <dd class="mt-0.5 text-gray-800">
                            @if($doc && $doc['url'] && $m->detail === $doc['label'])
                                <a href="{{ $doc['url'] }}" class="font-medium text-zertix-primary-700 hover:underline">{{ $doc['label'] }}</a>
                            @else
                                {{ $m->detail }}
                            @endif
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold text-gray-500">Registró</dt>
                    <dd class="mt-0.5 text-gray-800">{{ $m->creator->name ?? 'Sistema' }}</dd>
                </div>
                @if($m->isReversed())
                    <div>
                        <dt class="text-xs font-semibold text-gray-500">Anulado</dt>
                        <dd class="mt-0.5 text-gray-800">El {{ $m->reversedBy?->created_at?->format('d/m/Y h:i A') }}, con un movimiento contrario.</dd>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-gray-500">Nota</dt>
                    <dd class="mt-0.5 text-gray-800">{{ $m->notes ?: '—' }}</dd>
                </div>
            </dl>

            @if($editable)
                {{-- Acciones: anular (no debió existir) o corregir el monto (mal escrito) --}}
                <div class="mt-6 border-t border-gray-100 pt-5">
                    <div class="flex flex-wrap gap-2" x-show="!mode">
                        <x-ui.button variant="secondary" appearance="outline" size="sm" iconLeft="heroicon-s-pencil-square" x-on:click="mode = 'correct'">
                            Corregir monto
                        </x-ui.button>
                        <x-ui.button variant="error" appearance="outline" size="sm" iconLeft="heroicon-s-arrow-uturn-left" x-on:click="mode = 'void'">
                            Anular
                        </x-ui.button>
                    </div>

                    <form x-show="mode === 'void'" x-cloak method="POST" action="{{ route('finance.treasury.movements.void', $m) }}" class="flex flex-col gap-4">
                        @csrf @method('PATCH')
                        <input type="hidden" name="_form" value="{{ $name }}:void">
                        <p class="text-sm text-gray-600">
                            Para un movimiento que no debió existir. Se registra uno contrario y {{ $m->account->name }} queda en
                            <span class="font-semibold tabular-nums" :class="afterVoid < 0 ? 'text-state-error' : 'text-gray-900'" x-text="fmt(afterVoid)"></span>.
                            @if($isTransfer) La transferencia se anula en las dos cuentas. @endif
                        </p>
                        <x-ui.forms.input label="Motivo" name="void_reason" required placeholder="Ej: Se registró dos veces"
                            :value="$reopen === 'void' ? old('void_reason') : ''" :error="$reopen === 'void' ? $errors->first('void_reason') : null" />
                        <div class="flex justify-end gap-3">
                            <x-ui.button appearance="ghost" variant="secondary" x-on:click="mode = null">Volver</x-ui.button>
                            <x-ui.button type="submit" variant="error" disabledWhen="afterVoid < 0">Anular</x-ui.button>
                        </div>
                    </form>

                    <form x-show="mode === 'correct'" x-cloak method="POST" action="{{ route('finance.treasury.movements.correct', $m) }}" class="flex flex-col gap-4">
                        @csrf @method('PATCH')
                        <input type="hidden" name="_form" value="{{ $name }}:correct">
                        <p class="text-sm text-gray-600">
                            Para un monto mal escrito: se anula este y se registra el correcto, enlazados.
                            @if($isTransfer) Se corrige en las dos cuentas. @endif
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-ui.forms.input label="Monto correcto" name="correct_amount" type="number" step="0.01" min="0.01" required
                                :addonLeft="$currency" x-model="amount" :error="$reopen === 'correct' ? $errors->first('correct_amount') : null" />
                            <x-ui.forms.input label="Motivo" name="correct_reason" required placeholder="Ej: Eran 20,000, no 25,000"
                                :value="$reopen === 'correct' ? old('correct_reason') : ''" :error="$reopen === 'correct' ? $errors->first('correct_reason') : null" />
                        </div>
                        <p class="text-sm text-gray-600" x-show="afterCorrect !== null">
                            {{ $m->account->name }}: <span class="tabular-nums" x-text="fmt(balance)"></span> →
                            <span class="font-semibold tabular-nums" :class="afterCorrect < 0 ? 'text-state-error' : 'text-gray-900'" x-text="fmt(afterCorrect)"></span>
                            <span class="text-state-error" x-show="afterCorrect < 0">· saldo insuficiente</span>
                        </p>
                        <div class="flex justify-end gap-3">
                            <x-ui.button appearance="ghost" variant="secondary" x-on:click="mode = null">Volver</x-ui.button>
                            <x-ui.button type="submit" variant="primary" disabledWhen="afterCorrect === null || afterCorrect < 0">Corregir</x-ui.button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </x-modal>
@endforeach
