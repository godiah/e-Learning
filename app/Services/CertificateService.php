<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Courses;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use PDF;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class CertificateService
{
    protected $progressService;

    public function __construct(CourseProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    public function generateCertificate(Courses $course, User $user)
    {
        if (!$this->progressService->canAwardCertificate($course->id, $user->id)) {
            throw new \Exception('User is not eligible for a certificate');
        }

        $existingCertificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingCertificate) {
            return $existingCertificate;
        }

       try
       {
         // Generate unique certificate number
        $certificateNumber = $this->generateCertificateNumber();

        // Create certificate record
        $certificate = Certificate::create([
            'certificate_number' => $certificateNumber,
            'course_id' => $course->id,
            'user_id' => $user->id,
            'title' => "{$course->name} Completion Certificate",
            'description' => "This certifies that {$user->name} has successfully completed the course '{$course->title}' with distinction.",
            'completion_date' => now()
        ]);

        return $certificate;
       } catch (QueryException $e) {
            if ($e->getCode() === '23000') { // MySQL duplicate entry error code
                return Certificate::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->firstOrFail();
            }
            throw $e;
        }
    }

    private function generateCertificateNumber(): string
    {
        do {
            $number = strtoupper(date('Y') . '-' . Str::random(8));
        } while (Certificate::where('certificate_number', $number)->exists());

        return $number;
    }

    // public function generateLinkedInUrl(Certificate $certificate)
    // {
    //     $params = http_build_query([
    //         'name' => $certificate->title,
    //         'organizationName' => $certificate->organization_name,
    //         'issueYear' => $certificate->completion_date->year,
    //         'issueMonth' => $certificate->completion_date->month,
    //         'certUrl' => route('certificates.verify', $certificate->certificate_number)
    //     ]);

    //     return "https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&{$params}";
    // }
}
