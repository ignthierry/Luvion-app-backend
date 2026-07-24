<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatHistoryController extends Controller
{
    /**
     * Display a listing of chat histories.
     */
    public function index(Request $request)
    {
        $query = ChatHistory::query();

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->input('session_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('user_message', 'like', "%{$search}%")
                  ->orWhere('agent_response', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('intent') && $request->input('intent') !== 'all') {
            $query->where('intent', $request->input('intent'));
        }

        if ($request->filled('agent_type') && $request->input('agent_type') !== 'all') {
            $query->where('agent_type', $request->input('agent_type'));
        }

        $histories = $query->orderBy('created_at', 'desc')->get();

        return response()->json($histories);
    }

    /**
     * Get list of grouped chat sessions with stats.
     */
    public function sessions()
    {
        $sessions = DB::table('chat_histories')
            ->select(
                'session_id',
                DB::raw('COUNT(*) as total_messages'),
                DB::raw('MAX(created_at) as last_activity'),
                DB::raw('MAX(intent) as latest_intent'),
                DB::raw('MAX(agent_type) as agent_type')
            )
            ->groupBy('session_id')
            ->orderBy('last_activity', 'desc')
            ->get();

        return response()->json($sessions);
    }

    /**
     * Remove a chat history entry or whole session.
     */
    public function destroy(Request $request, $id)
    {
        if ($request->boolean('by_session')) {
            ChatHistory::where('session_id', $id)->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Seluruh sesi chat berhasil dihapus.'
            ]);
        }

        $chatHistory = ChatHistory::findOrFail($id);
        $chatHistory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Log chat berhasil dihapus.'
        ]);
    }
}
