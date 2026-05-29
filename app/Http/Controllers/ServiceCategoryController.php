<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    /**
     * Display a listing of the service categories.
     */
    public function index()
    {
        return response()->json(ServiceCategory::with('translations')->get());
    }
}
