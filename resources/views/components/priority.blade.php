@props(['priority'])

@php
    $definition = config("crm.priorities.{$priority}", ['label' => ucfirst($priority)]);
@endphp

<span {{ $attributes->merge(['class' => 'priority']) }}>
    <span class="priority-dot {{ $priority }}" aria-hidden="true"></span>{{ $definition['label'] }}
</span>
