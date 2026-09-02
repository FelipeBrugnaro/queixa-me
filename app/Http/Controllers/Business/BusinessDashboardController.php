<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ComplaintStage;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Ratings\Enums\StatsPeriod;
use App\Domain\Ratings\Models\CompanyStat;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BusinessDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $this->company($request);

        $this->seo()->title('Painel — '.$company->name);

        $pending = Complaint::published()
            ->where('company_id', $company->id)
            ->whereNull('first_response_at')
            ->with('category:id,name')
            ->orderBy('published_at')
            ->limit(8)
            ->get();

        return view('business.dashboard', [
            'company' => $company,
            'pending' => $pending,
            'counters' => $this->counters($company),
            'unreadMessages' => (int) Conversation::where('company_id', $company->id)->sum('company_unread_count'),
            'stat' => CompanyStat::where('company_id', $company->id)
                ->where('period_type', StatsPeriod::Rolling12->value)
                ->latest('period_start')
                ->first(),
        ]);
    }

    public function stats(Request $request): View
    {
        $company = $this->company($request);

        $this->seo()->title('Estatísticas — '.$company->name);

        $monthly = CompanyStat::where('company_id', $company->id)
            ->where('period_type', StatsPeriod::Monthly->value)
            ->orderByDesc('period_start')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        return view('business.stats', [
            'company' => $company,
            'monthly' => $monthly,
            'rolling' => CompanyStat::where('company_id', $company->id)
                ->where('period_type', StatsPeriod::Rolling12->value)
                ->latest('period_start')
                ->first(),
            'stageCounts' => Complaint::published()
                ->where('company_id', $company->id)
                ->selectRaw('stage, count(*) as total')
                ->groupBy('stage')
                ->pluck('total', 'stage'),
            'weights' => (array) config('queixame.index.weights'),
        ]);
    }

    /** @return array<string,int> */
    private function counters(Company $company): array
    {
        $base = Complaint::published()->where('company_id', $company->id);

        return [
            'total' => (clone $base)->count(),
            'awaiting' => (clone $base)->whereNull('first_response_at')->count(),
            'overdue' => (clone $base)
                ->whereNull('first_response_at')
                ->where('published_at', '<', now()->subDays((int) config('queixame.complaints.response_sla_days')))
                ->count(),
            'in_progress' => (clone $base)->whereIn('stage', [
                ComplaintStage::CompanyReplied->value,
                ComplaintStage::InFollowUp->value,
            ])->count(),
            'resolved' => (clone $base)->where('stage', ComplaintStage::Resolved->value)->count(),
            'this_month' => (clone $base)->where('published_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }
}
