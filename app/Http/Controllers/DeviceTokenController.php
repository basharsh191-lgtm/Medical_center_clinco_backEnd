<?php

namespace App\Http\Controllers;

use App\Models\DeviceTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceTokenController extends Controller
{
    public function saveDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);
        DeviceTokens::updateOrCreate(
            ['token' => $request->token],
            ['user_id' => $request->user()->id]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Device token saved successfully.'
        ]);
    }
    public function destroyDeviceToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);
        $user = Auth::user();
        $user->deviceTokens()->where('token', $request->token)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Device token removed successfully.',
        ], 200);
    }
}

