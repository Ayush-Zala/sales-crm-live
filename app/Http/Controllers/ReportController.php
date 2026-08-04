<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Disposition;
use App\Models\DispositionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Industry;
use Illuminate\Http\Request;
use App\Models\AssignCompanies;

class ReportController extends Controller
{
    public function dispositionListByCompanyId($companyId)
    {
        return Disposition::with(['company', 'user', 'client'])
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $role = $currentUser->getRoleNames();

        $report = $this->useGetAssignCompaniesReport($request);

        $userReport = $this->useGetUserReport($request);

        $dispositionReport = $this->useGetDispositionReport($request);

        if ($role->contains('Sales Executives')) {
            $user = User::where('id', Auth::id())->first();
        } else if ($role->contains('Business Development Manager')) {
            $user = User::where('reporting_authority_id', Auth::id())->where('is_active', true)->get();
        } else if ($role->contains('Business Development Team Lead')) {
            $reportingAuthority = User::where('id', Auth::id())->value('reporting_authority_id');
            $user = User::where('reporting_authority_id', $reportingAuthority)->where('is_active', true)->get();
        } else {
            $user = User::where("is_active", true)->role(['Sales Executives', 'Business Development Manager', 'Business Development Team Lead'])->get();
        }

        return Inertia::render('Report/index', [
            "report" => $report,
            "userReport" => $userReport,
            "dispositionReport" => $dispositionReport,
            "industryReport" => $this->getIndustryReport(),
            "industries" => Industry::all(),
            "users" => $user
        ]);
    }

    protected function useGetAssignCompaniesReport(Request $request)
    {
        $fromDate = urldecode($request->input('companyFromDate')) ?: now()->startOfMonth()->format('Y-m-d');
        $toDate = urldecode($request->input('companyToDate')) ?: now()->format('Y-m-d');
        $status = $request->input('companyAssignStatus') ?: 'active';

        $query = AssignCompanies::with(['assignTo', 'assignBy', 'company'])
            ->when($fromDate && $toDate, function ($q) use ($request, $fromDate, $toDate) {

                if ($fromDate > $toDate) {
                    abort(422, 'From date should be smaller than to date');
                }

                $q->whereBetween('assign_companies.created_at', [
                    $fromDate,
                    $toDate . ' 23:59:59'
                ]);
            })
            ->when($status, function ($q) use ($status) {
                if ($status == 'active') {
                    $q->where('is_active', true);
                } elseif ($status == 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('company', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->input('search') . '%');
                });
            })
            ->when($request->filled('user'), function ($q) use ($request) {
                $q->whereHas('assignTo', function ($q) use ($request) {
                    $q->where('id', $request->input('user'));
                })->orWhereHas('assignBy', function ($q) use ($request) {
                    $q->where('id', $request->input('user'));
                });
            })
            ->when($request->filled('industry'), function ($q) use ($request) {
                $industryFilter = urldecode($request->input('industry'));

                if ($industryFilter !== 'No Industry') {
                    $q->whereHas('company', function ($q) use ($industryFilter) {
                        $q->where('industry', "=", $industryFilter);
                    });
                }
            })->orderByDesc('assign_companies.created_at');

        return $query->paginate($request->input('per_page', 50))->appends($request->only('search'));
    }

    protected function useGetUserReport(Request $request)
    {
        $roles = ['Sales Executives', 'Business Development Manager', 'Business Development Team Lead'];

        $fromDate = urldecode($request->input('userFromDate')) ?: now()->startOfMonth()->format('Y-m-d');
        $toDate = urldecode($request->input('userToDate')) ?: now()->format('Y-m-d');
        $status = $request->input('userDataStatus') ?: 'active';

        // get user assign count data from assign_companies table
        $query = User::withCount([
            'assignedCompanies' => function ($q) use ($fromDate, $toDate, $status) {
                $q->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {

                    if ($fromDate > $toDate) {
                        abort(422, 'From date should be smaller than to date');
                    }

                    $q->whereBetween('assign_companies.created_at', [
                        $fromDate,
                        $toDate . ' 23:59:59'
                    ]);
                })->when($status, function ($q) use ($status) {
                    if ($status == 'active') {
                        $q->where('is_active', true);
                    } elseif ($status == 'inactive') {
                        $q->where('is_active', false);
                    }
                });
            },
        ])->with(["reportingAuthority"])->where("is_active", true)->role($roles)->orderByDesc('created_at');

        $users = $query->get();
        $totalAssignedCompanies = $users->sum('assigned_companies_count');

        return [
            'usersReport' => $users,
            'totalAssignedCompanies' => $totalAssignedCompanies,
        ];
    }

    protected function useGetDispositionReport($request)
    {
        $currentUser = auth()->user();
        $role = $currentUser->getRoleNames();

        $fromDate = urldecode($request->input('dispositionFromDate')) ?: now()->format('Y-m-d');
        $toDate = urldecode($request->input('dispositionToDate')) ?: now()->format('Y-m-d');

        $user = $role->contains('Sales Executives') ? Auth::id() : $request->input('dispositionUserFilter');

        $dispositions = DispositionStatus::withCount([
            'dispositions' => function ($q) use ($fromDate, $toDate, $user, $role, $currentUser) {
                $q->when($fromDate && $toDate, function ($q) use ($fromDate, $toDate) {
                    if ($fromDate > $toDate) {
                        abort(422, 'From date should be smaller than to date');
                    }

                    $q->whereBetween('dispositions.created_at', [
                        $fromDate,
                        $toDate . ' 23:59:59'
                    ]);
                })->when($user, function ($q) use ($user) {
                    // If a specific user is selected, use that user (only if not Sales Executive)
                    $q->whereHas('user', function ($q) use ($user) {
                        $q->where('id', $user);
                    });
                })->when($role->contains('Business Development Manager'), function ($q) use ($currentUser) {
                    // If user is BDM and no specific user is selected, show only their team
                    $q->whereHas('user', function ($q) use ($currentUser) {
                        $q->where('reporting_authority_id', Auth::id());
                    });
                });
            }
        ]);

        $dispositions = $dispositions->get();
        $total = $dispositions->sum('dispositions_count');

        return [
            'dispositions' => $dispositions,
            'totalDispositions' => $total,
        ];
    }

    protected function getIndustryReport()
    {
        // SELECT LOWER(TRIM(industry)) as industry, COUNT(*) as company_count
        // FROM companies
        // WHERE industry IS NOT NULL 
        // AND industry != ''
        // GROUP BY LOWER(TRIM(industry))
        // ORDER BY industry ASC;

        $query = Company::selectRaw("'No Industry' as industry, COUNT(*) as company_count")
            ->whereNull('industry')
            ->orWhere('industry', '')
            ->unionAll(
                Company::query()->selectRaw('LOWER(TRIM(industry)) as industry, COUNT(*) as company_count')
                    ->whereNotNull('industry')
                    ->where('industry', '!=', '')
                    ->groupByRaw('LOWER(TRIM(industry))')
            )
            ->orderBy('industry', 'ASC')
            ->get();

        $total = $query->sum('company_count');

        return [
            'industries' => $query,
            'totalIndustries' => $total,
        ];
    }
}
