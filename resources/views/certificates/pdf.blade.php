<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->course_title }} Certificate</title>
    @php
        $logoPath = public_path('images/mk-scholars-logo.webp');
        $logoData = file_exists($logoPath) ? 'data:image/webp;base64,'.base64_encode(file_get_contents($logoPath)) : null;
        $signatureData = $certificate->signatureImageDataUri();
        $certificateScore = $certificate->displayScore();
    @endphp
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f8fb;
            color: #073653;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        .page {
            width: 100%;
            padding: 36px;
        }

        .certificate {
            min-height: 680px;
            border: 10px double #ffc40c;
            background: #ffffff;
            padding: 46px;
        }

        .logo {
            width: 82px;
            height: 82px;
            margin: 0 auto 14px;
            border-radius: 999px;
        }

        .brand {
            color: #ffc40c;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 4px;
            text-align: center;
            text-transform: uppercase;
        }

        h1 {
            margin: 24px 0 0;
            color: #073653;
            font-size: 42px;
            line-height: 1.15;
            text-align: center;
        }

        .gold-rule {
            width: 160px;
            height: 5px;
            margin: 18px auto 22px;
            background: #ffc40c;
        }

        .intro,
        .verify {
            color: #475569;
            font-size: 14px;
            line-height: 1.8;
            text-align: center;
        }

        .student {
            margin: 18px 0;
            color: #073653;
            font-size: 34px;
            font-weight: 800;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .course {
            margin: 16px auto 0;
            max-width: 760px;
            color: #073653;
            font-size: 26px;
            font-weight: 700;
            line-height: 1.35;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .meta {
            margin-top: 42px;
            width: 100%;
            border-collapse: collapse;
        }

        .meta td {
            width: 25%;
            padding: 16px;
            border: 1px solid #e2e8f0;
            background: #f3f8fb;
            text-align: center;
            vertical-align: top;
        }

        .label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .value {
            margin-top: 8px;
            color: #073653;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
            word-break: break-word;
        }

        .skills {
            margin: 34px auto 0;
            max-width: 780px;
            text-align: center;
        }

        .skill {
            display: inline-block;
            margin: 4px;
            padding: 7px 11px;
            border-radius: 999px;
            background: #fff2b8;
            color: #073653;
            font-size: 11px;
            font-weight: 700;
        }

        .footer {
            margin-top: 44px;
            width: 100%;
        }

        .signature-wrap {
            display: inline-block;
            width: 260px;
            text-align: center;
        }

        .signature-image {
            display: block;
            max-width: 220px;
            max-height: 80px;
            margin: 0 auto 8px;
        }

        .signature-line {
            border-top: 2px solid #073653;
            padding-top: 10px;
        }

        .signer-name {
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .signer-title {
            margin-top: 4px;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
        }

        .verify-box {
            margin-top: 26px;
            padding: 14px;
            background: #fff2b8;
            color: #073653;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="certificate">
            @if ($logoData)
                <img src="{{ $logoData }}" alt="MK Scholars logo" class="logo">
            @endif
            <div class="brand">MK Scholars</div>
            <h1>Certificate of Completion</h1>
            <div class="gold-rule"></div>

            <p class="intro">This certificate is proudly presented to</p>
            <div class="student">{{ $certificate->student_name ?: ($certificate->user?->name ?? 'MK Scholars Student') }}</div>

            <p class="intro">for successfully completing</p>
            <div class="course">{{ $certificate->course_title ?: ($certificate->course?->title ?? 'MK Scholars Course') }}</div>

            <table class="meta">
                <tr>
                    <td>
                        <div class="label">Certificate No.</div>
                        <div class="value">{{ $certificate->certificate_number }}</div>
                    </td>
                    <td>
                        <div class="label">Verification Code</div>
                        <div class="value">{{ $certificate->verification_code }}</div>
                    </td>
                    <td>
                        <div class="label">Issued</div>
                        <div class="value">{{ $certificate->issued_at?->format('M j, Y') ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="label">Final Test Score</div>
                        <div class="value">{{ $certificateScore !== null ? $certificateScore.'%' : 'Not available' }}</div>
                    </td>
                </tr>
            </table>

            <div class="skills">
                <div class="label">Skills Acquired</div>
                <div style="margin-top: 12px;">
                    @forelse ($certificate->skills as $skill)
                        <span class="skill">{{ $skill->skill_name }}</span>
                    @empty
                        <span class="skill">Skills pending</span>
                    @endforelse
                </div>
            </div>

            <div class="verify-box">
                Verify this credential publicly at:<br>
                {{ $verificationUrl }}
            </div>

            <table class="footer">
                <tr>
                    <td style="width: 50%; color: #64748b; font-size: 12px;">
                        Issued by MK Scholars Learning Platform
                    </td>
                    <td style="width: 50%; text-align: right;">
                        <div class="signature-wrap">
                            @if ($signatureData)
                                <img src="{{ $signatureData }}" alt="Certificate signature" class="signature-image">
                            @endif
                            <div class="signature-line">
                                <div class="signer-name">{{ $certificate->signer_name ?: 'Authorized Signature' }}</div>
                                @if ($certificate->signer_title)
                                    <div class="signer-title">{{ $certificate->signer_title }}</div>
                                @elseif ($certificate->signer_name)
                                    <div class="signer-title">Authorized Signatory</div>
                                @endif
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
