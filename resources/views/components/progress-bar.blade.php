@props(['value' => 0, 'tone' => null, 'height' => null])

<span {{ $attributes->merge(['class' => 'progress-track']) }} @if ($height) style="height: {{ $height }}px" @endif role="progressbar" aria-valuenow="{{ (int) $value }}" aria-valuemin="0" aria-valuemax="100">
    <span class="progress-fill {{ $tone ? 'tone-'.$tone : '' }}" style="width: {{ max(0, min(100, (int) $value)) }}%"></span>
</span>
