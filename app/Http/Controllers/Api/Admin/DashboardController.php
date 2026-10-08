<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\SiteImage;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show()
    {
        return response()->json([
            'services' => Service::count(),
            'home_images' => SiteImage::where('page', 'home')->count(),
            'booking_images' => SiteImage::where('page', 'booking')->count(),
            'bookings' => Booking::whereNotNull('booking_date')->count(),
            'confirmed' => Booking::where('status', 'confirmed')->count(),
            'payments' => Payment::where('status', 'paid')->count(),
            'customers' => Customer::count(),
            'revenue' => (float) Payment::where('status', 'paid')->sum('amount'),
        ]);
    }
}
