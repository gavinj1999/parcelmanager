<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;

class AutomationController extends Controller
{
    /**
     * Handle automation task (e.g., screenshot) in the background.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function automate(Request $request)
    {
        try {
            // Validate input (e.g., URL to capture)
            $request->validate([
                'url' => 'required|url',
            ]);

            // Path to save the output (e.g., screenshot)
            $filename = 'screenshot-' . time() . '.png';
            $outputPath = storage_path('app/public/' . $filename);

            // Prepare the Browsershot command to run in the background
            $nodeBinary = '/usr/bin/node'; // Adjust path if needed
            $npmBinary = '/usr/bin/npm';   // Adjust path if needed
            $command = "node " . base_path('node_modules/puppeteer') . " " .
                       escapeshellarg($request->url) . " " .
                       escapeshellarg($outputPath) . " > /dev/null 2>&1 &";

            // Run the command in the background using shell_exec
            shell_exec($command);

            return response()->json([
                'message' => 'Automation task started in the background',
                'output' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Automation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to start automation task',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
