<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    /**
     * List all pending requests (accessible by providers).
     */
    public function index(Request $request)
    {
        $query = ServiceRequest::where('status', 'pending')->with('category', 'client');

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        if ($request->has('service_category_id')) {
            $query->where('service_category_id', $request->service_category_id);
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a new service request (Client only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'city' => 'required|string',
            'service_category_id' => 'required|exists:service_categories,id',
            'description' => 'required|string',
            'proposed_price' => 'required|numeric|min:0',
        ]);

        $serviceRequest = Auth::user()->serviceRequests()->create($validated);

        return response()->json($serviceRequest->load('category'), 201);
    }

    /**
     * Display the specified request with its offers.
     */
    public function show(ServiceRequest $serviceRequest)
    {
        return response()->json($serviceRequest->load(['category', 'client', 'offers.provider']));
    }

    /**
     * List requests created by the authenticated client.
     */
    public function myRequests()
    {
        return response()->json(
            Auth::user()->serviceRequests()->with(['category', 'offers.provider', 'selectedProvider'])->latest()->get()
        );
    }

    /**
     * Mark a request as completed.
     */
    public function complete(ServiceRequest $serviceRequest)
    {
        if (Auth::id() !== $serviceRequest->client_id && Auth::id() !== $serviceRequest->selected_provider_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $serviceRequest->update(['status' => 'completed']);

        return response()->json(['message' => 'Request marked as completed', 'request' => $serviceRequest]);
    }
}
