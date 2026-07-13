<?php

namespace App\Services;

use App\Models\Certificate;
use chillerlan\QRCode\QRCode;

class CertificateQrCodeService
{
    public function verificationUrl(Certificate $certificate): string
    {
        return route('certificates.verify', ['verification_code' => $certificate->verification_code]);
    }

    public function dataUri(Certificate $certificate): string
    {
        return (new QRCode())->render($this->verificationUrl($certificate));
    }
}
