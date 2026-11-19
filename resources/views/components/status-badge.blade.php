@props(['status', 'type' => 'project'])

@php
    $definition = config("crm.{$type}_statuses.{$status}", ['label' => ucfirst($status), 'tone' => 'secondary']);
@endphp

<span {{ $attributes->merge(['class' => "badge badge-soft tone-{$definition['tone']}"]) }}>{{ $definition['label'] }}</span>
