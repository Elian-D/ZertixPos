@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-zertix-primary-500 focus:ring-zertix-primary-500 rounded-md shadow-sm']) }}>
