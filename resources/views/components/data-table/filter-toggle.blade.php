@props(['label', 'name', 'options', 'formId'])

<div>
    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
        {{ $label }}
    </label>
    <div class="flex p-1 bg-gray-100 rounded-lg">
        @foreach($options as $value => $text)
            <label class="flex-1">
                <input type="radio" name="{{ $name }}" value="{{ $value }}" 
                    class="peer hidden" form="{{ $formId }}"
                    @checked( (string)request($name, '') === (string)$value )>
                <span class="block text-center px-2 py-1.5 text-xs font-medium rounded-md cursor-pointer transition-all
                    peer-checked:bg-white peer-checked:text-zertix-primary-600 peer-checked:shadow-sm
                    text-gray-500 hover:text-gray-700">
                    {{ $text }}
                </span>
            </label>
        @endforeach
    </div>
</div>