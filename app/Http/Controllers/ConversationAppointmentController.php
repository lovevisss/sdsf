<?php

namespace App\Http\Controllers;

use App\Models\ConversationAppointment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationAppointmentController extends Controller
{
    /**
     * List appointments for the current user, filtered by role and status.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status', 'pending');

        $query = ConversationAppointment::query();

        // Role-based scope
        if ($user->role === 'advisor') {
            $query->where('advisor_id', $user->id);
        } elseif ($user->role === 'student') {
            $query->where('student_id', $user->id);
        }
        // admin / leader: no restriction — see all appointments

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $appointments = $query
            ->with(['student', 'advisor'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Conversations/AppointmentConfirmation', [
            'appointments' => $appointments,
            'currentStatus' => $status,
            'statusCounts' => [
                'pending' => (int) ($statusCounts['pending'] ?? 0),
                'confirmed' => (int) ($statusCounts['confirmed'] ?? 0),
                'completed' => (int) ($statusCounts['completed'] ?? 0),
                'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            ],
        ]);
    }

    /**
     * Store a new appointment request from a student.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'advisor_id'       => 'required|exists:users,id',
            'appointment_type' => 'required|in:talk,consultation,other',
            'remarks'          => 'nullable|string',
        ]);

        ConversationAppointment::create([
            'student_id'       => $request->user()->id,
            'advisor_id'       => $validated['advisor_id'],
            'appointment_type' => $validated['appointment_type'],
            'remarks'          => $validated['remarks'] ?? null,
            'status'           => 'pending',
        ]);

        return redirect()->back()->with('success', '预约申请已发送！');
    }

    /**
     * Show appointment detail.
     */
    public function show(ConversationAppointment $appointment)
    {
        $this->authorize('view', $appointment);

        return Inertia::render('Conversations/AppointmentDetail', [
            'appointment' => $appointment->load(['student', 'advisor']),
        ]);
    }

    /**
     * Confirm a pending appointment.
     */
    public function confirm(ConversationAppointment $appointment)
    {
        $this->authorize('update', $appointment);

        $appointment->confirm();

        return redirect()->back()->with('success', '约谈已确认！');
    }

    /**
     * Cancel / delete an appointment.
     */
    public function destroy(ConversationAppointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', '约谈已取消。');
    }
}
