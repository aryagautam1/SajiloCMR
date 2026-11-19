@props(['user', 'size' => 'md', 'tone' => null])

@php
    $toneClasses = ['tone-1', 'tone-2', 'tone-3', 'tone-4', 'tone-5', 'tone-6'];
    $tone = $tone ?: $toneClasses[$user->id % count($toneClasses)];
    $sizeClass = $size === 'lg' ? 'avatar-lg' : ($size === 'sm' ? 'avatar-sm' : '');
@endphp

<span {{ $attributes->merge(['class' => "avatar {$sizeClass} {$tone}"]) }} title="{{ $user->name }}" aria-hidden="true">{{ $user->initials() }}</span>
