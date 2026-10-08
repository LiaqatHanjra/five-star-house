<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingAdminController extends Controller
{
    public function index()
    {
        $bookings = Booking::query()
            ->with(['payment', 'service'])
            ->whereNotNull('booking_date')
            ->latest()
            ->get()
            ->map->toPublicArray();

        return response()->json(['bookings' => $bookings]);
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])],
        ]);

        $booking->update($data);

        return response()->json(['booking' => $booking->fresh('payment')->toPublicArray()]);
    }
}
