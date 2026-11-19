<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Queries\ClientQuery;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(config('crm.client_statuses')))],
        ]);

        $clients = ClientQuery::build($filters)
            ->reorder()
            ->orderBy('company_name')
            ->get();

        $rows = $clients->map(fn (Client $client) => [
            $client->id,
            $client->name,
            $client->company_name,
            $client->email,
            $client->phone,
            $client->address,
            $client->statusLabel(),
            $client->projects_count,
            $client->created_at?->format('Y-m-d H:i'),
        ]);

        return Csv::download(
            'sajilo-clients-'.now()->format('Y-m-d').'.csv',
            ['ID', 'Contact Name', 'Company', 'Email', 'Phone', 'Address', 'Status', 'Projects', 'Created At'],
            $rows
        );
    }
}
