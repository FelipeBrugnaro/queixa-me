<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Accounts\Models\User;
use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\CompanyClaim;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Domain\Moderation\Models\DataRequest;
use App\Domain\Moderation\Models\Report;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->seo()->title('Administração');

        return view('admin.dashboard', [
            'queue' => [
                'moderation' => Complaint::pendingModeration()->count(),
                'sensitive' => Complaint::pendingModeration()->whereNotNull('sensitive_flags')->count(),
                'reports' => Report::where('status', ReportStatus::Open->value)->count(),
                'claims' => CompanyClaim::where('status', 'pending')->count(),
                'companies' => Company::where('status', CompanyStatus::Pending->value)->count(),
                // Pedidos RGPD com prazo a expirar são risco regulatório, não
                // apenas atraso operacional.
                'data_requests' => DataRequest::whereNull('completed_at')->count(),
                'data_requests_overdue' => DataRequest::whereNull('completed_at')
                    ->where('due_at', '<', now())->count(),
            ],
            'totals' => [
                'complaints' => Complaint::count(),
                'published' => Complaint::published()->count(),
                'resolved' => Complaint::where('stage', ComplaintStage::Resolved->value)->count(),
                'companies' => Company::public()->count(),
                'users' => User::count(),
            ],
            'recent' => Complaint::pendingModeration()
                ->with(['company:id,name,slug', 'user:id,uuid,public_name,status'])
                ->orderByDesc('priority')
                ->orderBy('submitted_at')
                ->limit(8)
                ->get(),
            'slaBreaches' => Complaint::published()
                ->whereNull('first_response_at')
                ->where('published_at', '<', now()->subDays((int) config('queixame.complaints.response_sla_days')))
                ->count(),
        ]);
    }
}
