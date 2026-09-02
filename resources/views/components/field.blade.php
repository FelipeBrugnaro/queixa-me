@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'options' => null,
    'placeholder' => null,
    'rows' => 6,
    'autocomplete' => null,
])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $current = old($name, $value);
    $describedBy = collect([
        $hint ? $id.'_hint' : null,
        $hasError ? $id.'_error' : null,
    ])->filter()->implode(' ');
@endphp

<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    <label for="{{ $id }}" class="label">
        {{ $label }}
        @if ($required)
            <span class="text-rose-600" aria-hidden="true">*</span>
            <span class="sr-only">(obrigatório)</span>
        @else
            <span class="ml-1 text-xs font-normal text-ink-400">(opcional)</span>
        @endif
    </label>

    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
                  @if($required) required @endif
                  @if($placeholder) placeholder="{{ $placeholder }}" @endif
                  @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                  @if($hasError) aria-invalid="true" @endif
                  {{ $attributes->except(['class', 'id']) }}
                  class="input textarea {{ $hasError ? 'input-error' : '' }}">{{ $current }}</textarea>

    @elseif ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}"
                @if($required) required @endif
                @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                @if($hasError) aria-invalid="true" @endif
                {{ $attributes->except(['class', 'id']) }}
                class="input {{ $hasError ? 'input-error' : '' }}">
            @unless ($required)
                <option value="">{{ $placeholder ?? '—' }}</option>
            @endunless
            @foreach (($options ?? []) as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>

    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
               value="{{ $type === 'password' ? '' : $current }}"
               @if($required) required @endif
               @if($placeholder) placeholder="{{ $placeholder }}" @endif
               @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
               @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if($hasError) aria-invalid="true" @endif
               {{ $attributes->except(['class', 'id']) }}
               class="input {{ $hasError ? 'input-error' : '' }}">
    @endif

    @if ($hint)
        <p id="{{ $id }}_hint" class="hint">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}_error" class="error-text" role="alert">
            <svg class="mt-px size-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 4a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V7a1 1 0 0 1 1-1Zm0 8.5a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Z" clip-rule="evenodd"/>
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>
