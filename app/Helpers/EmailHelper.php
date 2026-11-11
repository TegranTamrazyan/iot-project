<?php

declare(strict_types=1);

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class EmailHelper
{
    public static function buildReceiptHtml(array $order, array $items): string
    {
        $escape = static fn(mixed $value): string => htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        $rows = '';
        foreach ($items as $item) {
            $name = $escape($item['product_name'] ?? '');
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = number_format((float) ($item['unit_price'] ?? 0), 2);
            $subtotal = number_format((float) ($item['subtotal'] ?? 0), 2);

            $rows .= <<<HTML
                <tr>
                    <td style="padding:16px 12px;border-bottom:1px solid #e8edf2;color:#253247;font-size:14px;line-height:20px">{$name}</td>
                    <td style="padding:16px 12px;border-bottom:1px solid #e8edf2;color:#526174;text-align:center;font-size:14px">{$quantity}</td>
                    <td style="padding:16px 12px;border-bottom:1px solid #e8edf2;color:#526174;text-align:right;font-size:14px;white-space:nowrap">\${$unitPrice}</td>
                    <td style="padding:16px 12px;border-bottom:1px solid #e8edf2;color:#253247;text-align:right;font-size:14px;font-weight:700;white-space:nowrap">\${$subtotal}</td>
                </tr>
                HTML;
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="4" style="padding:20px 12px;color:#526174;text-align:center">No items were recorded for this purchase.</td></tr>';
        }

        $receiptId = (int) ($order['id'] ?? 0);
        $date = $escape($order['created_at'] ?? '');
        $total = number_format((float) ($order['total'] ?? 0), 2);
        $points = (int) ($order['points_earned'] ?? 0);

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Your IOT Smart Store receipt</title>
            </head>
            <body style="margin:0;padding:24px 12px;background-color:#f2f5f8;font-family:Arial,Helvetica,sans-serif;color:#253247">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse">
                    <tr>
                        <td align="center">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%;max-width:600px;border-collapse:separate;border-spacing:0;background-color:#ffffff;border:1px solid #e5eaf0;border-radius:12px;overflow:hidden">
                                <tr>
                                    <td style="padding:30px 32px;background-color:#172b4d;color:#ffffff">
                                        <div style="font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#b9c9df">IOT Smart Store</div>
                                        <h1 style="margin:10px 0 0;font-size:26px;line-height:32px;color:#ffffff">Thank you for your purchase</h1>
                                        <p style="margin:8px 0 0;font-size:14px;line-height:21px;color:#d9e3f0">Your payment was successful. Here is your receipt.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:28px 32px 12px">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse">
                                            <tr>
                                                <td style="padding:0 12px 16px 0;color:#718096;font-size:12px;text-transform:uppercase;letter-spacing:.6px">Receipt number</td>
                                                <td style="padding:0 0 16px;color:#718096;font-size:12px;text-align:right;text-transform:uppercase;letter-spacing:.6px">Date</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:0 12px 20px 0;color:#253247;font-size:16px;font-weight:700">#{$receiptId}</td>
                                                <td style="padding:0 0 20px;color:#253247;font-size:14px;text-align:right">{$date}</td>
                                            </tr>
                                        </table>
                                        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse">
                                            <thead>
                                                <tr style="background-color:#f5f8fb">
                                                    <th align="left" style="padding:12px;color:#526174;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Item</th>
                                                    <th align="center" style="padding:12px;color:#526174;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Qty</th>
                                                    <th align="right" style="padding:12px;color:#526174;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Price</th>
                                                    <th align="right" style="padding:12px;color:#526174;font-size:11px;text-transform:uppercase;letter-spacing:.5px">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>{$rows}</tbody>
                                        </table>
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:22px;border-collapse:collapse">
                                            <tr>
                                                <td style="padding:8px 0;color:#526174;font-size:14px">Loyalty points earned</td>
                                                <td align="right" style="padding:8px 0;color:#253247;font-size:14px;font-weight:700">{$points} points</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:16px 0 4px;border-top:2px solid #e8edf2;color:#253247;font-size:16px;font-weight:700">Total paid</td>
                                                <td align="right" style="padding:16px 0 4px;border-top:2px solid #e8edf2;color:#087e75;font-size:22px;font-weight:700">\${$total}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px 32px;background-color:#f8fafc;color:#718096;font-size:12px;line-height:19px;text-align:center">
                                        Thank you for shopping with IOT Smart Store.<br>
                                        This email contains your purchase receipt.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            HTML;
    }

    public static function sendReceipt(
        string $recipient,
        string $receipt
    ): bool {
        try {
            $host = (string) (getenv('MAIL_HOST') ?: ($_ENV['MAIL_HOST'] ?? ''));
            $username = (string) (getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? ''));
            $password = (string) (getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? ''));
            $port = (int) (getenv('MAIL_PORT') ?: ($_ENV['MAIL_PORT'] ?? 587));
            $encryption = strtolower((string) (getenv('MAIL_ENCRYPTION') ?: ($_ENV['MAIL_ENCRYPTION'] ?? 'tls')));

            if (
                $host === ''
                || $username === ''
                || $password === ''
                || $port < 1
                || $port > 65535
                || !in_array($encryption, ['tls', 'ssl'], true)
            ) {
                throw new \RuntimeException('SMTP configuration is incomplete or invalid.');
            }

            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = $encryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $port;

            $mail->setFrom(
                $username,
                'IOT Smart Store'
            );

            $mail->addAddress($recipient);

            $mail->isHTML(true);
            $mail->Subject = 'Your Purchase Receipt';
            $mail->Body = $receipt;

            $mail->send();

            return true;
        } catch (Throwable $e) {
            error_log('Email error: ' . $e->getMessage());
            return false;
        }
    }
}
