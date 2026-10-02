<?php
/*
 * The two emails enquire.php sends for each enquiry, in the "Enquiry Auto-reply" design (layout 1a from the Claude Design project:
 * dark header band, card body, row list):
 *   - send_notification(): the full enquiry, to Mark (the config's 'to' address). Reply-To is the visitor.
 *   - send_autoreply(): the "thanks, your enquiry is in" confirmation, to the visitor. Reply-To is Mark.
 * Included by enquire.php only; .htaccess refuses direct requests.
 *
 * Email-client rules this follows: tables for layout, inline styles (the <style> block only adds mobile and dark-mode tweaks),
 * a PNG logo (Gmail and Outlook do not show SVG), absolute image URLs, and every visitor value HTML-escaped.
 */
declare(strict_types=1);

if (!defined('ENQUIRY_EMAILS')) {
    http_response_code(404);
    exit;
}

const EMAIL_SITE = 'https://distinctgraphicdesigns.com.au';
const EMAIL_FONT = "'Avenir Next',Avenir,Helvetica,Arial,sans-serif";

/** HTML-escape a value; blank values show as $blank. */
function email_esc(string $value, string $blank = 'Not provided'): string
{
    return $value === '' ? $blank : htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** The visitor's first name, for greetings. */
function email_first_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name));
    return is_array($parts) && $parts[0] !== '' ? $parts[0] : 'there';
}

/** The current time in NSW, e.g. "Fri 2 Oct 2026, 2:24 pm AEST". */
function email_local_time(): string
{
    return (new DateTime('now', new DateTimeZone('Australia/Sydney')))->format('D j M Y, g:i a T');
}

/** Small uppercase section label. */
function email_eyebrow(string $text, string $margin = '0 0 16px'): string
{
    return '<p class="accent" style="margin:' . $margin . ';font-size:11px;line-height:14px;mso-line-height-rule:exactly;letter-spacing:1.1px;text-transform:uppercase;color:#416180;">' . $text . '</p>';
}

/** One label/value row of a summary list. $valueHtml must already be escaped. */
function email_row(string $label, string $valueHtml, int $size = 15, int $lineHeight = 20): string
{
    return <<<HTML
    <tr><td class="line" style="padding:14px 0;border-bottom:1px solid #d4d4d7;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>
        <td class="stack muted" width="140" valign="top" style="width:140px;font-size:13px;line-height:20px;color:#5d5d60;">{$label}</td>
        <td class="stack ink" valign="top" style="font-size:{$size}px;line-height:{$lineHeight}px;color:#1d1f20;word-break:break-word;">{$valueHtml}</td>
      </tr></table>
    </td></tr>

HTML;
}

/** A summary list: a ruled heading followed by rows. */
function email_list(string $heading, string $rowsHtml): string
{
    $font = EMAIL_FONT;
    return <<<HTML
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family:{$font};">
    <tr><td class="accent line" style="padding:0 0 12px;border-bottom:1px solid #1d1f20;font-size:11px;line-height:14px;mso-line-height-rule:exactly;letter-spacing:1.1px;text-transform:uppercase;color:#416180;">{$heading}</td></tr>
{$rowsHtml}  </table>
HTML;
}

/** A solid button that works in Outlook too. */
function email_button(string $href, string $label): string
{
    $font = EMAIL_FONT;
    return <<<HTML
<td class="stack" style="padding:0 12px 12px 0;"><table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td class="btn" bgcolor="#1d2d3d" style="background:#1d2d3d;"><a href="{$href}" style="display:inline-block;white-space:nowrap;padding:13px 22px;font-family:{$font};font-size:13px;line-height:16px;letter-spacing:1.1px;text-transform:uppercase;color:#f2f2f3;text-decoration:none;">{$label}</a></td></tr></table></td>
HTML;
}

/**
 * The shared page: preheader, outer background, card with the dark logo band, then $bodyRows (each a <tr>), then the footer plate.
 */
function email_page(string $title, string $preheader, string $bodyRows, string $footerHtml): string
{
    $site = EMAIL_SITE;
    $font = EMAIL_FONT;
    return <<<HTML
<!DOCTYPE html>
<html lang="en-AU" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>{$title}</title>
<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
<style>
  body { margin:0; padding:0; }
  a:hover { color:#2c455d !important; }
  @media (max-width:620px) {
    .outer { padding:0 !important; }
    .card { border-left:0 !important; border-right:0 !important; }
    .px { padding-left:24px !important; padding-right:24px !important; }
    .h1 { font-size:30px !important; line-height:32px !important; }
    .stack { display:block !important; width:100% !important; }
    td.stack + td.stack { padding-top:4px !important; }
  }
  @media (prefers-color-scheme: dark) {
    .bg { background:#1a1c1e !important; }
    .card { background:#24272a !important; }
    .ink { color:#e7e7ea !important; }
    .muted { color:#b7b7ba !important; }
    .line { border-color:#424244 !important; }
    .plate { background:#2c455d !important; }
    .accent { color:#94bce3 !important; }
    .btn { background:#416180 !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#f2f2f3;">
<span style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">{$preheader}&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</span>
<table role="presentation" class="bg" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f2f3" style="background:#f2f2f3;">
<tr><td class="outer" align="center" style="padding:32px 12px;">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" class="card" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#f9f9fa" style="width:100%;max-width:600px;background:#f9f9fa;border:1px solid #d4d4d7;">

<!-- Header band -->
<tr><td class="px" bgcolor="#1d2d3d" style="background:#1d2d3d;padding:28px 40px;">
  <a href="{$site}/" style="text-decoration:none;"><img src="{$site}/assets/email/logo-v2-rev.png" width="180" height="62" alt="Distinct Graphic Designs" style="display:block;width:180px;max-width:180px;height:auto;border:0;color:#f2f2f3;font-family:{$font};font-size:16px;font-weight:bold;"></a>
</td></tr>

{$bodyRows}

<!-- Footer -->
<tr><td class="px plate" bgcolor="#eef6ff" style="background:#eef6ff;border-top:1px solid #1d1f20;padding:24px 40px;font-family:{$font};">
{$footerHtml}
</td></tr>

</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
HTML;
}

/** Send a multipart/alternative (plain text + HTML) email. $html may be null to send plain text only. */
function email_send(string $to, string $subject, string $text, ?string $html, array $headers, string $from): bool
{
    $headers[] = 'MIME-Version: 1.0';
    if ($html === null) {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        $body = quoted_printable_encode($text);
    } else {
        $boundary = 'dgd-' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $body = implode("\r\n", [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($text),
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($html),
            '--' . $boundary . '--',
            '',
        ]);
    }
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    return @mail($to, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $from);
}

/* ---------------------------------------------------------------------------------------------------------------------------
 * Notification to Mark
 * ------------------------------------------------------------------------------------------------------------------------ */

/** "Where it came from" rows: only the values that are present, so the list stays short. Each item is [label, html]. */
function notification_source_rows(array $in): array
{
    $rows = [];
    $rows[] = ['Sent from', email_esc($in['page'], 'Unknown')];
    if ($in['landing_page'] !== '' && $in['landing_page'] !== $in['page']) {
        $rows[] = ['First landed on', email_esc($in['landing_page'])];
    }
    $rows[] = ['Referrer', email_esc($in['referrer'], 'Direct or unknown')];
    $utm = array_filter([
        'source' => $in['utm_source'], 'medium' => $in['utm_medium'], 'campaign' => $in['utm_campaign'],
        'term' => $in['utm_term'], 'content' => $in['utm_content'],
    ], 'strlen');
    if ($utm !== []) {
        $parts = [];
        foreach ($utm as $key => $value) {
            $parts[] = $key . ': ' . email_esc($value);
        }
        $rows[] = ['Campaign (UTM)', implode('<br>', $parts)];
    }
    if ($in['gclid'] !== '') {
        $rows[] = ['Google Ads click', 'Yes <span class="muted" style="color:#5d5d60;">(' . email_esc(cut($in['gclid'], 24)) . '…)</span>'];
    }
    if ($in['ph_distinct_id'] !== '') {
        $url = 'https://us.posthog.com/project/620068/person/' . rawurlencode($in['ph_distinct_id']);
        $rows[] = ['PostHog', '<a class="accent" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" style="color:#416180;">View visitor</a>'];
    }
    return $rows;
}

function notification_text(array $in, ?int $id, string $received): string
{
    $lines = [];
    if ($id === null) {
        $lines[] = '!! NOT saved to the database. This email is the only copy of this enquiry.';
        $lines[] = '';
    }
    $lines[] = 'NEW ENQUIRY' . ($id !== null ? ' #' . $id : '') . ' · ' . $in['service'];
    $lines[] = '';
    $lines[] = $in['name'];
    $lines[] = $in['email'];
    $lines[] = $in['phone'] !== '' ? $in['phone'] : 'No phone given';
    $lines[] = '';
    $lines[] = 'PROJECT DETAILS';
    $lines[] = str_replace("\n", "\r\n", $in['details']);
    $lines[] = '';
    $lines[] = 'WHERE IT CAME FROM';
    $lines[] = 'Sent from: ' . ($in['page'] !== '' ? $in['page'] : 'Unknown');
    $lines[] = 'First landed on: ' . $in['landing_page'];
    $lines[] = 'Referrer: ' . ($in['referrer'] !== '' ? $in['referrer'] : 'Direct or unknown');
    $lines[] = 'UTM source / medium / campaign: ' . $in['utm_source'] . ' / ' . $in['utm_medium'] . ' / ' . $in['utm_campaign'];
    $lines[] = 'UTM term / content: ' . $in['utm_term'] . ' / ' . $in['utm_content'];
    $lines[] = 'Google click ID: ' . ($in['gclid'] !== '' ? $in['gclid'] : 'None');
    $lines[] = 'PostHog ID: ' . ($in['ph_distinct_id'] !== '' ? $in['ph_distinct_id'] : 'None');
    $lines[] = '';
    $lines[] = '--';
    $lines[] = 'Received ' . $received . '. Reply to this email to answer ' . email_first_name($in['name']) . ' directly.';
    return implode("\r\n", $lines);
}

function notification_html(array $in, ?int $id, string $received): string
{
    $font = EMAIL_FONT;
    $name = email_esc($in['name']);
    $first = email_esc(email_first_name($in['name']));
    $service = email_esc($in['service']);
    $number = $id !== null ? ' #' . $id : '';
    $preheader = email_esc(cut($in['service'] . ' · ' . preg_replace('/\s+/u', ' ', $in['details']), 140));

    $warning = '';
    if ($id === null) {
        $warning = <<<HTML
<tr><td class="px" style="padding:24px 40px 0;font-family:{$font};">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td bgcolor="#fdecea" style="background:#fdecea;border-left:4px solid #b3261e;padding:14px 16px;font-size:14px;line-height:20px;color:#5c1410;"><strong>Not saved to the database.</strong> This email is the only copy of this enquiry, so keep it.</td></tr></table>
</td></tr>
HTML;
    }

    $subjectLine = rawurlencode('Re: Your enquiry with Distinct Graphic Designs');
    $buttons = email_button('mailto:' . htmlspecialchars(rawurlencode($in['email']), ENT_QUOTES, 'UTF-8') . '?subject=' . $subjectLine, 'Reply to ' . $first);
    if ($in['phone'] !== '') {
        $tel = preg_replace('/[^0-9+]/', '', $in['phone']);
        $buttons .= email_button('tel:' . htmlspecialchars((string) $tel, ENT_QUOTES, 'UTF-8'), 'Call ' . email_esc($in['phone']));
    }

    $details = nl2br(email_esc($in['details']), false);
    $emailLink = '<a class="accent" href="mailto:' . htmlspecialchars(rawurlencode($in['email']), ENT_QUOTES, 'UTF-8') . '" style="color:#416180;">' . email_esc($in['email']) . '</a>';
    $contactRows = email_row('Email', $emailLink)
        . email_row('Phone', email_esc($in['phone'], 'Not given'))
        . email_row('Service', $service);
    $sourceRows = '';
    foreach (notification_source_rows($in) as [$label, $html]) {
        $sourceRows .= email_row($label, $html, 13, 19);
    }
    $contactList = email_list('Contact', $contactRows);
    $sourceList = email_list('Where it came from', $sourceRows);
    $detailsEyebrow = email_eyebrow('Project details', '0 0 12px');
    $headEyebrow = email_eyebrow('New enquiry' . $number);

    $body = <<<HTML
{$warning}
<!-- Headline -->
<tr><td class="px" style="padding:36px 40px 0;font-family:{$font};">
  {$headEyebrow}
  <h1 class="h1 ink" style="margin:0;font-size:38px;line-height:38px;mso-line-height-rule:exactly;font-weight:600;letter-spacing:-0.5px;text-transform:uppercase;color:#1d1f20;">{$name}<br><span class="accent" style="font-weight:400;color:#416180;">{$service}</span></h1>
</td></tr>

<!-- Actions -->
<tr><td class="px" style="padding:24px 40px 0;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>{$buttons}</tr></table>
</td></tr>

<!-- Project details -->
<tr><td class="px" style="padding:20px 40px 0;font-family:{$font};">
  {$detailsEyebrow}
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td class="plate" bgcolor="#eef6ff" style="background:#eef6ff;border-left:3px solid #416180;padding:18px 20px;">
    <p class="ink" style="margin:0;font-size:16px;line-height:25px;color:#1d1f20;word-break:break-word;">{$details}</p>
  </td></tr></table>
</td></tr>

<!-- Contact -->
<tr><td class="px" style="padding:36px 40px 0;">
{$contactList}
</td></tr>

<!-- Source -->
<tr><td class="px" style="padding:36px 40px 40px;">
{$sourceList}
</td></tr>
HTML;

    $footer = '<p class="muted" style="margin:0;font-size:12px;line-height:18px;color:#5d5d60;">Received ' . email_esc($received)
        . ($id !== null ? ' · saved as enquiry #' . $id . ' in the <code style="font-size:12px;">enquiries</code> table' : '') . '.</p>'
        . '<p class="muted" style="margin:10px 0 0;font-size:12px;line-height:18px;color:#5d5d60;">Hit reply to answer ' . $first . ' directly.</p>';

    return email_page('New enquiry' . $number, $preheader, $body, $footer);
}

/** Email the full enquiry to Mark. Always sends: if the HTML cannot be built, it falls back to plain text, because this may be the only copy. */
function send_notification(array $in, ?int $id, string $to, string $from): bool
{
    $received = email_local_time();
    try {
        $html = notification_html($in, $id, $received);
    } catch (Throwable $e) {
        error_log('enquire.php: notification HTML failed, sending plain text: ' . $e->getMessage());
        $html = null;
    }
    $subject = 'New enquiry' . ($id !== null ? ' #' . $id : ' (NOT SAVED)') . ': ' . $in['service'] . ' from ' . $in['name'];
    return email_send($to, $subject, notification_text($in, $id, $received), $html, [
        'From: "Distinct Graphic Designs website" <' . $from . '>',
        'Reply-To: ' . $in['email'],
    ], $from);
}

/* ---------------------------------------------------------------------------------------------------------------------------
 * Confirmation to the visitor
 * ------------------------------------------------------------------------------------------------------------------------ */

function autoreply_text(array $in): string
{
    return implode("\r\n", [
        'Thanks, ' . email_first_name($in['name']) . '. Your enquiry is in.',
        '',
        'I’ve received your enquiry and will come back to you within a day. A copy of what you sent is below. If you need to add anything, just reply to this email.',
        '',
        'YOUR ENQUIRY',
        'Service: ' . $in['service'],
        'Email: ' . $in['email'],
        'Phone: ' . ($in['phone'] !== '' ? $in['phone'] : 'Not provided'),
        'Project details:',
        str_replace("\n", "\r\n", $in['details']),
        '',
        'WHAT HAPPENS NEXT',
        '01  I read through your details and reply within a day.',
        '02  If it’s a good fit, we book a short call to talk through scope, timing and budget.',
        '',
        'Mark Lee',
        'Distinct Graphic Designs',
        'mark@distinctgraphicdesigns.com.au',
        '0423 927 847',
        '',
        '--',
        'You’re receiving this because you sent an enquiry at distinctgraphicdesigns.com.au. This is a one-off reply, not a mailing list.',
        '© ' . gmdate('Y') . ' Distinct Graphic Designs · Uranquinty NSW, Australia',
    ]);
}

function autoreply_html(array $in): string
{
    $site = EMAIL_SITE;
    $font = EMAIL_FONT;
    $firstName = email_esc(email_first_name($in['name']));
    $year = gmdate('Y');
    $rows = email_row('Service', email_esc($in['service']))
        . email_row('Email', email_esc($in['email']))
        . email_row('Phone', email_esc($in['phone']))
        . email_row('Project details', nl2br(email_esc($in['details']), false), 15, 22);
    $summary = email_list('Your enquiry', $rows);
    $receivedEyebrow = email_eyebrow('Enquiry received');
    $nextEyebrow = email_eyebrow('What happens next');

    $body = <<<HTML
<!-- Hero image -->
<tr><td bgcolor="#1d2d3d" style="background:#1d2d3d;padding:0;">
  <img src="{$site}/assets/og-image.jpg" width="600" alt="A collage of design and marketing work by Distinct Graphic Designs" style="display:block;width:100%;max-width:600px;height:auto;border:0;">
</td></tr>

<!-- Headline -->
<tr><td class="px" style="padding:40px 40px 0;font-family:{$font};">
  {$receivedEyebrow}
  <h1 class="h1 ink" style="margin:0;font-size:38px;line-height:38px;mso-line-height-rule:exactly;font-weight:600;letter-spacing:-0.5px;text-transform:uppercase;color:#1d1f20;">Thanks, {$firstName}.<br><span class="accent" style="font-weight:400;color:#416180;">Your enquiry is in.</span></h1>
  <p class="ink" style="margin:20px 0 0;font-size:16px;line-height:24px;mso-line-height-rule:exactly;color:#1d1f20;">I’ve received your enquiry and will come back to you within a day. A copy of what you sent is below. If you need to add anything, just reply to this email.</p>
</td></tr>

<!-- Enquiry summary -->
<tr><td class="px" style="padding:32px 40px 0;">
{$summary}
</td></tr>

<!-- What happens next -->
<tr><td class="px" style="padding:40px 40px 0;font-family:{$font};">
  {$nextEyebrow}
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td class="accent" width="48" valign="top" style="width:48px;padding:0 0 16px;font-size:13px;line-height:22px;letter-spacing:1.3px;color:#416180;">01</td>
      <td class="ink" valign="top" style="padding:0 0 16px;font-size:15px;line-height:22px;color:#1d1f20;">I read through your details and reply within a day.</td>
    </tr>
    <tr>
      <td class="accent" width="48" valign="top" style="width:48px;font-size:13px;line-height:22px;letter-spacing:1.3px;color:#416180;">02</td>
      <td class="ink" valign="top" style="font-size:15px;line-height:22px;color:#1d1f20;">If it’s a good fit, we book a short call to talk through scope, timing and budget.</td>
    </tr>
  </table>
</td></tr>

<!-- Sign-off -->
<tr><td class="px" style="padding:40px 40px 40px;font-family:{$font};">
  <p class="ink" style="margin:0;font-size:15px;line-height:22px;color:#1d1f20;">Mark Lee<br><span class="muted" style="color:#5d5d60;">Distinct Graphic Designs</span></p>
  <p style="margin:12px 0 0;font-size:15px;line-height:24px;">
    <a class="accent" href="mailto:mark@distinctgraphicdesigns.com.au" style="color:#416180;text-decoration:underline;">mark@distinctgraphicdesigns.com.au</a><br>
    <a class="accent" href="tel:+61423927847" style="color:#416180;text-decoration:underline;">0423 927 847</a>
  </p>
</td></tr>
HTML;

    $footer = '<p class="muted" style="margin:0;font-size:12px;line-height:18px;color:#5d5d60;">You’re receiving this because you sent an enquiry at <a class="accent" href="' . $site . '/" style="color:#416180;">distinctgraphicdesigns.com.au</a>. This is a one-off reply, not a mailing list.</p>'
        . '<p class="muted" style="margin:10px 0 0;font-size:12px;line-height:18px;color:#5d5d60;">© ' . $year . ' Distinct Graphic Designs · Uranquinty NSW, Australia</p>';

    return email_page('Thanks, your enquiry has been received', 'I’ve got your enquiry and will come back to you within a day. A copy of what you sent is inside.', $body, $footer);
}

/** Send the confirmation to the visitor. Replies go to $replyTo (Mark's inbox). */
function send_autoreply(array $in, string $from, string $replyTo): bool
{
    return email_send($in['email'], 'Thanks, your enquiry has been received', autoreply_text($in), autoreply_html($in), [
        // The display name must be quoted: unquoted, its comma splits From into two addresses and Gmail rejects the message.
        'From: "Mark Lee, Distinct Graphic Designs" <' . $from . '>',
        'Reply-To: ' . $replyTo,
        'Auto-Submitted: auto-replied',
        'X-Auto-Response-Suppress: All',
    ], $from);
}
