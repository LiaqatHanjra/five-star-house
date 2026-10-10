<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class StudioAdminController extends Controller
{
    public function payments()
    {
        $payments = Payment::query()->with('booking')->latest()->get()->map->toPublicArray();

        return response()->json(['payments' => $payments]);
    }

    public function customers()
    {
        $customers = Customer::query()->withCount('bookings')->latest()->get()->map(fn (Customer $customer) => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'bookings_count' => $customer->bookings_count,
            'created_at' => $customer->created_at?->toIso8601String(),
        ]);

        return response()->json(['customers' => $customers]);
    }

    public function settings()
    {
        return response()->json(['settings' => SiteSetting::allPairs()]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'minimum_hours' => ['required', 'integer', 'min:2', 'max:12'],
            'studio_minimum_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'currency' => ['required', 'string', 'size:3'],
            'studio_hourly_rate' => ['required', 'numeric', 'min:0'],
            'event_hourly_rate' => ['required', 'numeric', 'min:0'],
            'owner_hourly_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $data['currency'] = strtoupper($data['currency']);
        SiteSetting::putPairs($data);

        return response()->json(['settings' => SiteSetting::allPairs()]);
    }
}
