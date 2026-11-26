<?php
declare(strict_types=1);

/**
 * Branded Responsive HTML Email Template for GlassCart Email Verification.
 *
 * Variables passed:
 * @var string $name
 * @var string $email
 * @var string $otp
 * @var string $verifyUrl
 * @var int    $expiresInMinutes
 * @var string $appUrl
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Your GlassCart Account</title>
  <style>
    /* Client-specific Resets */
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
    body { margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
    
    @media only screen and (max-width: 600px) {
      .email-container { width: 100% !important; padding: 16px !important; }
      .otp-box { font-size: 32px !important; letter-spacing: 6px !important; padding: 16px !important; }
      .btn-verify { width: 100% !important; box-sizing: border-box !important; }
    }
  </style>
</head>
<body style="margin: 0; padding: 32px 16px; background-color: #f1f5f9;">

  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center">
        <!-- Container Card -->
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="560" class="email-container" style="max-width: 560px; width: 100%; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04); border: 1px solid #e2e8f0;">
          
          <!-- Brand Header -->
          <tr>
            <td style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%); padding: 36px 32px; text-align: center;">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td align="center">
                    <!-- GlassCart Monogram / Icon -->
                    <div style="display: inline-block; width: 48px; height: 48px; line-height: 48px; border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #ffffff; font-size: 22px; font-weight: 800; text-align: center; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); margin-bottom: 12px;">
                      GC
                    </div>
                    <div style="font-size: 24px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">
                      Glass<span style="color: #818cf8;">Cart</span>
                    </div>
                    <div style="font-size: 13px; color: #94a3b8; margin-top: 4px; font-weight: 500;">
                      Next-Gen Premium E-Commerce
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Email Content Body -->
          <tr>
            <td style="padding: 40px 36px; background-color: #ffffff;">
              <h2 style="margin: 0 0 16px; font-size: 20px; font-weight: 700; color: #0f172a; line-height: 1.3;">
                Verify your email address
              </h2>

              <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #475569;">
                Hello <strong style="color: #0f172a;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></strong>,
              </p>

              <p style="margin: 0 0 24px; font-size: 15px; line-height: 1.6; color: #475569;">
                Thank you for joining GlassCart. To complete your registration and activate your account, please enter the following 6-digit verification code:
              </p>

              <!-- Prominent OTP Display Box -->
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
                <tr>
                  <td align="center" style="background-color: #f8fafc; border: 2px dashed #6366f1; border-radius: 12px; padding: 20px 24px;">
                    <span style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #6366f1; margin-bottom: 8px;">
                      Verification Code
                    </span>
                    <span class="otp-box" style="display: block; font-family: 'SF Mono', Consolas, Monaco, monospace; font-size: 38px; font-weight: 800; letter-spacing: 8px; color: #0f172a; line-height: 1.2;">
                      <?= htmlspecialchars($otp, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span style="display: block; font-size: 12px; color: #64748b; margin-top: 8px; font-weight: 500;">
                      ⏱ Expires in <strong><?= (int)$expiresInMinutes ?> minutes</strong>
                    </span>
                  </td>
                </tr>
              </table>

              <!-- Instant Activation Button -->
              <p style="margin: 0 0 16px; font-size: 14px; line-height: 1.5; color: #475569; text-align: center;">
                Or activate your account instantly by clicking the button below:
              </p>

              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                <tr>
                  <td align="center">
                    <a href="<?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn-verify" style="display: inline-block; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff; font-size: 15px; font-weight: 600; text-decoration: none; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); letter-spacing: 0.01em;">
                      Verify Email Address &rarr;
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Alternative URL for plain clients -->
              <div style="background-color: #f8fafc; border-radius: 8px; padding: 12px 16px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
                <p style="margin: 0 0 6px; font-size: 12px; color: #64748b; font-weight: 600;">Button not working? Paste this URL into your browser:</p>
                <p style="margin: 0; font-size: 12px; color: #6366f1; word-break: break-all; line-height: 1.4;">
                  <a href="<?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?>" style="color: #6366f1; text-decoration: underline;"><?= htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') ?></a>
                </p>
              </div>

              <!-- Security Notice -->
              <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 24px 0;">

              <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #94a3b8;">
                <strong>Security Notice:</strong> If you did not create a GlassCart account with this email (<span style="color: #64748b;"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>), please ignore this message. No account will be activated without this verification.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 24px 36px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; text-align: center;">
              <p style="margin: 0 0 8px; font-size: 12px; color: #64748b;">
                &copy; <?= date('Y') ?> GlassCart E-Commerce. All rights reserved.
              </p>
              <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                This is an automated security transmission. Please do not reply directly to this email.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
