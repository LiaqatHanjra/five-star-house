<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Booking::query()
            ->latest()
            ->get()
            ->map->toPublicArray();

        return response()->json(['inquiries' => $inquiries]);
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'reviewed', 'archived'])],
        ]);

        $booking->update($data);

        return response()->json(['inquiry' => $booking->fresh()->toPublicArray()]);
    }
}
