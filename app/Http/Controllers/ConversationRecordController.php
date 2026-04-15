<?php

namespace App\Http\Controllers;

use App\Models\ConversationRecord;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationRecordController extends Controller
{
    /**
     * Get conversation history with filters.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ConversationRecord::with(['advisor', 'student', 'classModel.department']);

        // Apply role-based access control
        if ($user->role === 'advisor') {
            $query->where('advisor_id', $user->id);
        } elseif ($user->role === 'leader') {
            $query->whereHas('classModel', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });
        } elseif ($user->role !== 'admin') {
            // Students can only see their own records
            $query->where('student_id', $user->id);
        }

        // Apply filters
        if ($request->has('class_id')) {
            $query->where('class_model_id', $request->query('class_id'));
        }
        if ($request->has('advisor_id')) {
            $query->where('advisor_id', $request->query('advisor_id'));
        }
        if ($request->has('topic')) {
            $query->where('topic', 'like', '%' . $request->query('topic') . '%');
        }
        if ($request->has('method')) {
            $query->where('conversation_method', $request->query('method'));
        }
        if ($request->has('date_from')) {
            $query->whereDate('conversation_at', '>=', $request->query('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('conversation_at', '<=', $request->query('date_to'));
        }

        $records = $query->latest('conversation_at')->paginate(20);

        return Inertia::render('Conversations/RecordHistory', [
            'records' => $records,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Show form to create new conversation record.
     */
    public function create(Request $request)
    {
        $this->authorize('create', ConversationRecord::class);

        return Inertia::render('Conversations/RecordCreate', [
            'classes' => ClassModel::with('department')->get(),
        ]);
    }

    /**
     * Store new conversation record.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ConversationRecord::class);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'class_model_id' => 'required|exists:class_models,id',
            'conversation_form' => 'required|in:talk,consultation,sport,meal,tea_break,seminar,other',
            'conversation_method' => 'required|in:one_on_one,one_on_many,dorm_visit,class_meeting,family_contact',
            'conversation_reason' => 'required|in:academic,life,psychology,discipline,other',
            'topic' => 'required|string|max:255',
            'content' => 'nullable|string',
            'conversation_at' => 'required|date',
            'location' => 'nullable|string|max:255',
        ]);

        ConversationRecord::create([
            'advisor_id' => $request->user()->id,
            ...$validated,
        ]);

        return redirect()->route('conversation-records.index')->with('success', 'Conversation record logged successfully!');
    }

    /**
     * Show conversation record detail.
     */
    public function show(ConversationRecord $record)
    {
        $this->authorize('view', $record);

        return Inertia::render('Conversations/RecordDetail', [
            'record' => $record->load(['advisor', 'student', 'classModel.department']),
        ]);
    }

    /**
     * Show edit form.
     */
    public function edit(ConversationRecord $record)
    {
        $this->authorize('update', $record);

        return Inertia::render('Conversations/RecordEdit', [
            'record' => $record,
            'classes' => ClassModel::with('department')->get(),
        ]);
    }

    /**
     * Update conversation record.
     */
    public function update(Request $request, ConversationRecord $record)
    {
        $this->authorize('update', $record);

        $validated = $request->validate([
            'conversation_form' => 'required|in:talk,consultation,sport,meal,tea_break,seminar,other',
            'conversation_method' => 'required|in:one_on_one,one_on_many,dorm_visit,class_meeting,family_contact',
            'conversation_reason' => 'required|in:academic,life,psychology,discipline,other',
            'topic' => 'required|string|max:255',
            'content' => 'nullable|string',
            'conversation_at' => 'required|date',
            'location' => 'nullable|string|max:255',
        ]);

        $record->update($validated);

        return redirect()->route('conversation-records.show', $record)->with('success', 'Record updated successfully!');
    }

    /**
     * Delete conversation record.
     */
    public function destroy(ConversationRecord $record)
    {
        $this->authorize('delete', $record);

        $record->delete();

        return redirect()->route('conversation-records.index')->with('success', 'Record deleted.');
    }
}
