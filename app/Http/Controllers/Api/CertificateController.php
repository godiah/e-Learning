<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CertificateService;
use App\Models\Courses;
use App\Models\Certificate;
use App\Http\Resources\CertificateResource;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    protected $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    public function generate(Request $request, Courses $course)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            $existingCertificate = Certificate::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingCertificate) {
                return response()->json([
                    'success' => true,
                    'message' => 'Certificate already exists',
                    'data' => new CertificateResource($existingCertificate)
                ]);
            }
            
            $certificate = $this->certificateService->generateCertificate($course, $user);
            //$linkedinUrl = $this->certificateService->generateLinkedInUrl($certificate);

            //$certificate->update(['linkedin_url' => $linkedinUrl]);

            return response()->json([
                'success' => true,
                'message' => 'Certificate generated successfully',
                'data' => new CertificateResource($certificate)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verify($certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)
            ->with(['user:id,name,email', 'course:id,title'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new CertificateResource($certificate)
        ]);
    }
}
