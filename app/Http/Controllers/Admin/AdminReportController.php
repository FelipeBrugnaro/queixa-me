<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Services\ComplaintWorkflow;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function __construct(private readonly ComplaintWorkflow $workflow) {}

    public function index(Request $request): View
    {
        $this->seo()->title('Denúncias');

        $reports = Report::query()
            ->when($request->query('estado', ReportStatus::Open->value), fn ($q, $status) => $q->where('status', $status))
            ->with(['reporter:id,uuid,public_name,name,status', 'reporterCompany:id,name,slug', 'reportable'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'statuses' => ReportStatus::options(),
            'activeStatus' => (string) $request->query('estado', ReportStatus::Open->value),
        ]);
    }

    public function decide(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:uphold,dismiss'],
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
            // Só faz sentido remover conteúdo quando a denúncia procede.
            'remove_content' => ['nullable', 'boolean'],
        ], [], ['notes' => 'fundamentação']);

        $upheld = $data['decision'] === 'uphold';

        $report->update([
            'status' => $upheld ? ReportStatus::Upheld : ReportStatus::Dismissed,
            'resolution_notes' => $data['notes'],
            'resolved_by_user_id' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        if ($upheld && $request->boolean('remove_content') && $report->reportable instanceof Complaint) {
            $this->workflow->remove($report->reportable, $request->user(), $data['notes']);
        }

        return back()->with('success', 'Denúncia decidida.');
    }
}
