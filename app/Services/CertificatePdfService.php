<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CertificatePdfService
{
    public function download(Certificate $certificate): Response
    {
        $certificate->loadMissing(['skills', 'course.instructor', 'user']);

        $filename = $this->filename($certificate);
        $qrCodeService = app(CertificateQrCodeService::class);
        $assetService = app(CertificateAssetService::class);
        $settings = CertificateSetting::current();
        $verificationUrl = $qrCodeService->verificationUrl($certificate);
        $viewData = [
            'certificate' => $certificate,
            'verificationUrl' => $verificationUrl,
            'qrCodeDataUri' => $qrCodeService->dataUri($certificate),
            'certificateSettings' => $settings,
            'stampDataUri' => $assetService->dataUri($settings->stamp_path),
            'issuerSignatureDataUri' => $assetService->dataUri($settings->admin_signature_path),
            'instructorSignatureDataUri' => $assetService->dataUri($certificate->instructor()?->signature_path),
            'certificateLogoDataUri' => $assetService->dataUri($settings->logo_path),
        ];

        if (class_exists('Barryvdh\\DomPDF\\Facade\\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('certificates.pdf', $viewData)
                ->setPaper('a4', 'landscape');

            return $pdf->download($filename.'.pdf');
        }

        return response()
            ->view('certificates.pdf', $viewData)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'.html"');
    }

    private function filename(Certificate $certificate): string
    {
        $course = Str::slug($certificate->course_title ?: 'certificate');
        $number = Str::slug($certificate->certificate_number ?: 'mk-scholars');

        return 'mk-scholars-'.$course.'-'.$number;
    }
}
