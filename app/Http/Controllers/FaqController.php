<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function index()
    {
        // If request wants all data (admin dashboard)
        if (request()->has('all')) {
            $faqs = Faq::orderBy('sort_order')->get();
        } else {
            $faqs = Faq::where('is_active', true)
                       ->orderBy('sort_order')
                       ->get();
        }
                   
        return response()->json($faqs);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question_id' => 'required|string|max:255',
            'answer_id' => 'required|string',
            'question_en' => 'required|string|max:255',
            'answer_en' => 'required|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $faq = Faq::create($validated);
        return response()->json($faq, 201);
    }

    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        
        $validated = $request->validate([
            'question_id' => 'sometimes|required|string|max:255',
            'answer_id' => 'sometimes|required|string',
            'question_en' => 'sometimes|required|string|max:255',
            'answer_en' => 'sometimes|required|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $faq->update($validated);
        return response()->json($faq);
    }

    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        
        return response()->json(['message' => 'Deleted successfully']);
    }
}
