<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Support\BookingPageContent;
use App\Support\HomePageContent;
use App\Support\ServicesPageContent;
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
            'owner_email_secondary' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:180'],
            'open_time' => ['required', 'date_format:H:i'],
            'close_time' => ['required', 'date_format:H:i'],
            'minimum_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'studio_minimum_hours' => ['required', 'integer', 'min:1', 'max:12'],
            'currency' => ['required', 'string', 'size:3'],
            'studio_hourly_rate' => ['required', 'numeric', 'min:0'],
            'event_hourly_rate' => ['required', 'numeric', 'min:0'],
            'owner_hourly_rate' => ['required', 'numeric', 'min:0'],
            'seo_title_en' => ['nullable', 'string', 'max:70'],
            'seo_title_fr' => ['nullable', 'string', 'max:70'],
            'seo_description_en' => ['nullable', 'string', 'max:160'],
            'seo_description_fr' => ['nullable', 'string', 'max:160'],
        ]);

        $data['currency'] = strtoupper($data['currency']);
        SiteSetting::putPairs($data);

        return response()->json(['settings' => SiteSetting::allPairs()]);
    }

    public function bookingPage()
    {
        return response()->json(['content' => BookingPageContent::resolved()]);
    }

    public function updateBookingPage(Request $request)
    {
        $rules = [];
        foreach (BookingPageContent::LOCALES as $locale) {
            foreach (BookingPageContent::FIELDS as $field) {
                $rules[$locale.'.'.$field] = ['required', 'string', 'max:'.BookingPageContent::limit($field)];
            }
        }

        $data = $request->validate($rules);

        foreach (BookingPageContent::LOCALES as $locale) {
            SiteSetting::putPairs([
                'booking_page_'.$locale => json_encode($data[$locale], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return response()->json(['content' => BookingPageContent::resolved()]);
    }

    public function servicesPage()
    {
        return response()->json(['content' => ServicesPageContent::resolved()]);
    }

    public function updateServicesPage(Request $request)
    {
        $rules = [];
        foreach (ServicesPageContent::LOCALES as $locale) {
            $rules[$locale.'.heading'] = ['required', 'string', 'max:240'];
            $rules[$locale.'.description'] = ['required', 'string', 'max:800'];
            $rules[$locale.'.tags'] = ['required', 'array', 'min:1', 'max:8'];
            $rules[$locale.'.tags.*'] = ['required', 'string', 'max:80'];
        }

        $data = $request->validate($rules);

        foreach (ServicesPageContent::LOCALES as $locale) {
            SiteSetting::putPairs([
                'services_page_'.$locale => json_encode([
                    'heading' => ServicesPageContent::heading($data[$locale]['heading']),
                    'description' => trim($data[$locale]['description']),
                    'tags' => ServicesPageContent::tags($data[$locale]['tags']),
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return response()->json(['content' => ServicesPageContent::resolved()]);
    }

    public function homePage()
    {
        return response()->json(['content' => HomePageContent::resolved()]);
    }

    public function updateHomePage(Request $request)
    {
        $rules = [];
        foreach (HomePageContent::LOCALES as $locale) {
            $rules[$locale.'.heading'] = ['required', 'string', 'max:240'];
            $rules[$locale.'.description'] = ['required', 'string', 'max:800'];
            $rules[$locale.'.tags'] = ['required', 'array', 'min:1', 'max:8'];
            $rules[$locale.'.tags.*'] = ['required', 'string', 'max:80'];
            $rules[$locale.'.button'] = ['required', 'string', 'max:60'];
            $rules[$locale.'.button_secondary'] = ['required', 'string', 'max:60'];
        }

        $data = $request->validate($rules);

        foreach (HomePageContent::LOCALES as $locale) {
            SiteSetting::putPairs([
                'home_page_'.$locale => json_encode([
                    'heading' => ServicesPageContent::heading($data[$locale]['heading']),
                    'description' => trim($data[$locale]['description']),
                    'tags' => ServicesPageContent::tags($data[$locale]['tags']),
                    'button' => trim($data[$locale]['button']),
                    'button_secondary' => trim($data[$locale]['button_secondary']),
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return response()->json(['content' => HomePageContent::resolved()]);
    }
}
