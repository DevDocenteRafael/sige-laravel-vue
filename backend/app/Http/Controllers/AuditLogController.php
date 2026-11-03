<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogsExport;
use App\Models\AuditLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildQuery($request, withUserSearch: true);

        $logs = $query->orderByDesc('created_at')->paginate(20);

        $stats = [
            'total'        => AuditLog::count(),
            'last_24h'     => AuditLog::where('created_at', '>=', now()->subDay())->count(),
            'active_users' => AuditLog::where('created_at', '>=', now()->subDay())->distinct('user_id')->count('user_id'),
            'most_common'  => AuditLog::selectRaw('action, count(*) as total')
                                ->groupBy('action')
                                ->orderByDesc('total')
                                ->value('action'),
        ];

        return response()->json([
            'logs'  => $logs,
            'stats' => $stats,
        ]);
    }

    public function export(Request $request)
    {
        $logs = $this->buildQuery($request, withUserSearch: true)
            ->orderByDesc('created_at')
            ->get();

        return match ($request->query('format', 'csv')) {
            'xlsx'  => Excel::download(new AuditLogsExport($logs), 'audit-logs.xlsx'),
            'pdf'   => Pdf::loadView('exports.audit-logs-pdf', ['logs' => $logs])
                           ->setPaper('a4', 'landscape')
                           ->download('audit-logs.pdf'),
            default => $this->streamCsv($logs),
        };
    }

    private function buildQuery(Request $request, bool $withUserSearch = false): Builder
    {
        $query = AuditLog::with('user');

        if ($request->search) {
            $query->where(function ($q) use ($request, $withUserSearch) {
                $q->where('action', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');

                if ($withUserSearch) {
                    $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $request->search . '%')
                                                         ->orWhere('email', 'like', '%' . $request->search . '%'));
                }
            });
        }

        if ($request->period) {
            $days = match ($request->period) {
                '7d'  => 7,
                '30d' => 30,
                '90d' => 90,
                default => 30,
            };
            $query->where('created_at', '>=', now()->subDays($days));
        }

        if ($request->action && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        return $query;
    }

    private function streamCsv(\Illuminate\Support\Collection $logs)
    {
        $csv = "Data/Hora,Usuário,Email,Ação,Descrição,IP\n";

        foreach ($logs as $log) {
            $csv .= implode(',', [
                '"' . $log->created_at->format('d/m/Y H:i:s') . '"',
                '"' . ($log->user?->name  ?? 'Sistema') . '"',
                '"' . ($log->user?->email ?? '-') . '"',
                '"' . $log->action . '"',
                '"' . str_replace('"', '""', $log->description ?? '') . '"',
                '"' . ($log->ip_address ?? '-') . '"',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-logs.csv"',
        ]);
    }

}