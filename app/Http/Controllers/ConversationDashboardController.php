<?php

namespace App\Http\Controllers;

use App\Models\ConversationRecord;
use App\Models\ConversationAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConversationDashboardController extends Controller
{
    /**
     * Get conversation overview dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $year = now()->year;

        // Build base query based on user role
        $recordQuery = ConversationRecord::query();

        if ($user->role === 'advisor') {
            $recordQuery->where('advisor_id', $user->id);
        } elseif ($user->role === 'leader') {
            $recordQuery->whereHas('classModel', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        }

        // Year conversation count
        $yearlyCount = (clone $recordQuery)->whereYear('conversation_at', $year)->count();

        // Top topic
        $topTopic = (clone $recordQuery)->whereYear('conversation_at', $year)
            ->select('topic', DB::raw('count(*) as count'))
            ->groupBy('topic')
            ->orderByDesc('count')
            ->first();

        // Top method
        $topMethod = (clone $recordQuery)->whereYear('conversation_at', $year)
            ->select('conversation_method', DB::raw('count(*) as count'))
            ->groupBy('conversation_method')
            ->orderByDesc('count')
            ->first();

        // Pending appointments
        $pendingAppointments = ConversationAppointment::where('advisor_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // Pending records (confirmed appointments = took place but not yet logged)
        $pendingRecords = ConversationAppointment::where('advisor_id', $user->id)
            ->where('status', 'confirmed')
            ->count();

        // Topic distribution
        $topicDistribution = (clone $recordQuery)->whereYear('conversation_at', $year)
            ->select('topic', DB::raw('count(*) as count'))
            ->groupBy('topic')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Monthly trend
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', conversation_at) AS INTEGER)"
            : 'MONTH(conversation_at)';

        $monthlyTrend = (clone $recordQuery)
            ->selectRaw($monthExpression.' as month, COUNT(*) as count')
            ->whereYear('conversation_at', $year)
            ->groupBy(DB::raw($monthExpression))
            ->orderBy(DB::raw($monthExpression))
            ->get();

        // Method distribution
        $methodDistribution = (clone $recordQuery)->whereYear('conversation_at', $year)
            ->select('conversation_method', DB::raw('count(*) as count'))
            ->groupBy('conversation_method')
            ->get();

        // Top students
        $topStudents = (clone $recordQuery)->whereYear('conversation_at', $year)
            ->select('student_id', DB::raw('count(*) as count'))
            ->with('student')
            ->groupBy('student_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Class distribution
        $classDistribution = (clone $recordQuery)->whereYear('conversation_at', $year)
            ->select('class_model_id', DB::raw('count(*) as count'))
            ->with('classModel')
            ->groupBy('class_model_id')
            ->orderByDesc('count')
            ->get();

        return Inertia::render('Conversations/Dashboard', [
            'stats' => [
                'yearlyCount' => $yearlyCount,
                'pendingAppointments' => $pendingAppointments,
                'pendingRecords' => $pendingRecords,
                'topTopic' => $topTopic?->topic ?? 'N/A',
                'topMethod' => $topMethod?->conversation_method ?? 'N/A',
            ],
            'charts' => [
                'topicDistribution' => $topicDistribution,
                'monthlyTrend' => $monthlyTrend,
                'methodDistribution' => $methodDistribution,
                'topStudents' => $topStudents,
                'classDistribution' => $classDistribution,
            ],
        ]);
    }
}
