<?php

return [
    'currency' => 'NPR',

    'client_statuses' => [
        'active' => ['label' => 'Active', 'tone' => 'success', 'icon' => 'bi-check-circle-fill'],
        'inactive' => ['label' => 'Inactive', 'tone' => 'secondary', 'icon' => 'bi-dash-circle-fill'],
        'prospect' => ['label' => 'Prospect', 'tone' => 'info', 'icon' => 'bi-stars'],
    ],

    'project_statuses' => [
        'planning' => ['label' => 'Planning', 'tone' => 'info', 'icon' => 'bi-compass'],
        'not_started' => ['label' => 'Not Started', 'tone' => 'secondary', 'icon' => 'bi-pause-circle'],
        'in_progress' => ['label' => 'In Progress', 'tone' => 'primary', 'icon' => 'bi-arrow-repeat'],
        'on_hold' => ['label' => 'On Hold', 'tone' => 'warning', 'icon' => 'bi-pause-btn'],
        'completed' => ['label' => 'Completed', 'tone' => 'success', 'icon' => 'bi-check2-circle'],
        'cancelled' => ['label' => 'Cancelled', 'tone' => 'danger', 'icon' => 'bi-x-circle'],
    ],

    'priorities' => [
        'low' => ['label' => 'Low', 'tone' => 'secondary'],
        'medium' => ['label' => 'Medium', 'tone' => 'info'],
        'high' => ['label' => 'High', 'tone' => 'warning'],
        'urgent' => ['label' => 'Urgent', 'tone' => 'danger'],
    ],

    'chart_colors' => [
        'planning' => '#0ea5e9',
        'not_started' => '#94a3b8',
        'in_progress' => '#2563eb',
        'on_hold' => '#f59e0b',
        'completed' => '#16a34a',
        'cancelled' => '#dc2626',
        'active' => '#16a34a',
        'inactive' => '#94a3b8',
        'prospect' => '#0ea5e9',
        'low' => '#94a3b8',
        'medium' => '#0ea5e9',
        'high' => '#f59e0b',
        'urgent' => '#dc2626',
    ],

    'per_page_options' => [10, 25, 50],

    'max_logo_kb' => 2048,
];
