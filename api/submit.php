<?php
date_default_timezone_set('America/New_York');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── CONFIG ──────────────────────────────────────────────
require_once __DIR__ . '/config.local.php'; // defines RESEND_API_KEY, AIRTABLE_TOKEN, RECAPTCHA_SECRET — not tracked in git

define('AIRTABLE_BASE',  'appOLvneFXafiB6Dj');
define('NOTIFY_EMAIL',   'webcontact@keystoneprep.org');
define('FROM_EMAIL',     'notifications@notifications.keystoneprep.org');
define('FROM_NAME',      'Keystone Prep HS Website');

$TABLES = [
    'tour'        => 'tbl9ZLUNj9bv6ofiY',
    'contact'     => 'tbluSIITATv7VhUnB',
    'camp'        => 'tblbKYr5PcMDCykTt',
    'admissions'  => 'tblH6YNxjIAhLRXup',
    'donor'       => 'tbleVTjoOvyr43tlV',
    'job'         => 'tblVmIfpmd4bfR6V0',
    'misconduct'  => 'tbl8Mymc37Cgn2qIp',
    'application' => 'tbl1fptjuTNcebkGk',
    'alumni'      => 'tblFcIpD9b8YV16mo',
    'community_service' => 'tbljgvhzVNn5KGqmO',
];

// ── FIELD MAPS ──────────────────────────────────────────
// Maps form field names → Airtable column names for each form type
$FIELD_MAPS = [
    'tour' => [
        'first_name'     => '_combine_name',
        'last_name'      => '_combine_name',
        'email'          => 'Email',
        'phone'          => 'Phone',
        'student_name'   => 'Student Name',
        'grade'          => 'Grade',
        'preferred_date' => 'Preferred Date',
        'preferred_time' => 'Preferred Time',
        'referral_source'=> 'Referral Source',
        'message'        => 'Message',
    ],
    'contact' => [
        'first_name' => '_combine_name',
        'last_name'  => '_combine_name',
        'email'      => 'Email',
        'phone'      => 'Phone',
        'subject'    => 'Subject',
        'message'    => 'Message',
    ],
    'camp' => [
        'parent_name'    => 'Parent Name',
        'parent_email'   => 'Parent Email',
        'parent_phone'   => 'Parent Phone',
        'student_first'  => 'Student First Name',
        'student_last'   => 'Student Last Name',
        'student_grade'  => 'Student Grade',
        'student_school' => 'Current School',
        'camp_choice'    => 'Camp Choice',
        'camp_referral'  => 'Referral',
        'camp_notes'     => 'Notes',
    ],
    'admissions' => [
        'first_name'     => '_combine_name',
        'last_name'      => '_combine_name',
        'email'          => 'Email',
        'phone'          => 'Phone',
        'best_time'      => 'Best Time',
        'student_name'   => 'Student Name',
        'current_school' => 'Current School',
        'grade'          => 'Grade',
        'school_year'    => 'School Year',
        'referral'       => 'Referral',
    ],
    'donor' => [
        'first_name'       => '_combine_name',
        'last_name'        => '_combine_name',
        'email'            => 'Email',
        'phone'            => 'Phone',
        'giving_level'     => 'Giving Level',
        'gift_type'        => 'Gift Type',
        'project_interest' => 'Project Interest',
        'message'          => 'Message',
    ],
    'job' => [
        'first_name' => '_combine_name',
        'last_name'  => '_combine_name',
        'email'      => 'Email',
        'phone'      => 'Phone',
        'position'   => 'Position',
        'message'    => 'Message',
    ],
    'misconduct' => [
        'first_name'     => '_combine_reporter',
        'last_name'      => '_combine_reporter',
        'email'          => 'Reporter Email',
        'phone'          => 'Reporter Phone',
        'reporter_role'  => 'Reporter Role',
        'subject_name'   => 'Subject Name',
        'misconduct_type'=> 'Misconduct Type',
        'incident_date'  => 'Incident Date',
        'description'    => 'Description',
    ],
    'application' => [
        'student_first'         => '_combine_student',
        'student_last'          => '_combine_student',
        'student_dob'           => 'Student DOB',
        'student_grade_apply'   => 'Applying Grade',
        'student_school_year'   => 'School Year',
        'student_current_school'=> 'Current School',
        'student_current_grade' => 'Current Grade',
        'parent1_name'          => 'Parent 1 Name',
        'parent1_relationship'  => 'Parent 1 Relationship',
        'parent1_phone'         => 'Parent 1 Phone',
        'parent1_email'         => 'Parent 1 Email',
        'parent2_name'          => 'Parent 2 Name',
        'parent2_relationship'  => 'Parent 2 Relationship',
        'parent2_phone'         => 'Parent 2 Phone',
        'parent2_email'         => 'Parent 2 Email',
        'home_street'           => '_combine_address',
        'home_city'             => '_combine_address',
        'home_state'            => '_combine_address',
        'home_zip'              => '_combine_address',
        'best_time'             => 'Best Time',
        'previous_schools'      => 'Previous Schools',
        'discipline_history'    => 'Discipline History',
        'discipline_explain'    => 'Discipline Explain',
        'virtual_school'        => 'Virtual School',
        'has_iep'               => 'Has IEP',
        'has_504'               => 'Has 504 Plan',
        'has_psychoed'          => 'Psychoeducational Evaluation',
        'support_areas'         => 'Support Areas',
        'student_strengths'     => 'Student Strengths',
        'student_challenges'    => 'Student Challenges',
        'additional_context'    => 'Additional Context',
        'magnet_interest'       => 'Magnet Program Interest',
        'campus_visit'          => 'Campus Visit',
        'referral_source'       => 'Referral Source',
        'referral_detail'       => 'Referral Detail',
        'any_other'             => 'Additional Notes',
    ],
    'alumni' => [
        'first_name'      => '_combine_name',
        'last_name'       => '_combine_name',
        'maiden_name'     => 'Maiden Name',
        'graduation_year' => 'Graduation Year',
        'date_of_birth'   => 'Date of Birth',
        'email'           => 'Email',
        'phone'           => 'Phone',
        'street_address'  => 'Street Address',
        'city'            => 'City',
        'state'           => 'State',
        'zip_code'        => 'ZIP Code',
        'employer'        => 'Employer',
        'job_title'       => 'Job Title',
        'college'         => 'College',
        'interests'       => 'Interests',
        'program_interest'=> 'Program Interest',
        'reunion'         => 'Reunion',
        'message'         => 'Notes',
    ],
    'community_service' => [
        'first_name'         => '_combine_name',
        'last_name'          => '_combine_name',
        'email'              => 'Email',
        'phone'              => 'Phone',
        'activity_name'      => 'Activity/Job/Event',
        'hours_served'       => 'Hours Served',
        'supervisor_name'    => 'Supervisor Name',
        'supervisor_phone'   => 'Supervisor Phone',
        'description'        => 'Description',
    ],
];

// ── EMAIL SUBJECTS ──────────────────────────────────────
$EMAIL_SUBJECTS = [
    'tour'        => 'New Tour Request',
    'contact'     => 'New Contact Inquiry',
    'camp'        => 'New Camp Registration',
    'admissions'  => 'New Admissions Inquiry',
    'donor'       => 'New Donor Interest',
    'job'         => 'New Job Application',
    'misconduct'  => 'New Misconduct Report',
    'application' => 'New Student Application',
    'alumni'      => 'Alumni Contact Update',
    'community_service' => 'New Community Service Hours Submission',
];

// ── ANTI-SPAM CONFIG ───────────────────────────────────
define('RECAPTCHA_THRESHOLD', 0.5);

// ── EMAIL HELPER ─────────────────────────────────────────
function send_resend_email($to, $subject, $text, $html = null, $replyTo = null) {
    $payload = [
        'from'    => FROM_NAME . ' <' . FROM_EMAIL . '>',
        'to'      => [$to],
        'subject' => $subject,
        'text'    => $text,
    ];
    if ($html) $payload['html'] = $html;
    if ($replyTo) $payload['reply_to'] = $replyTo;

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode >= 200 && $httpCode < 300);
}

// ── HTML NOTIFICATION TEMPLATE ──────────────────────────
// Builds the staff-facing notification email as a table-based HTML layout
// (inline styles throughout — required for consistent rendering across
// Outlook/Gmail/Apple Mail). All submitted values are escaped since they
// come directly from public form input.
function build_notification_html($title, $badgeLabel, $fields, $airtableUrl) {
    $rows = '';
    foreach ($fields as $label => $val) {
        if ($label === 'Status' || $label === 'Submitted At') continue;
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $safeVal   = nl2br(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
        $rows .= '
        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #EDF0F3;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#98A2B3;width:36%;vertical-align:top;">' . $safeLabel . '</td>
          <td style="padding:12px 0;border-bottom:1px solid #EDF0F3;font-size:14px;color:#1D2939;line-height:1.5;vertical-align:top;">' . $safeVal . '</td>
        </tr>';
    }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $badge = htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8');
    $submittedAt = htmlspecialchars(date('M j, Y g:i A T'), ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#F7F9FB;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F9FB;padding:32px 16px;font-family:Arial,Helvetica,sans-serif;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;border:1px solid #D0D5DD;overflow:hidden;">
          <tr>
            <td style="background:#0F4C81;padding:24px 32px;">
              <div style="color:#DCEBFA;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;">Keystone Prep High School</div>
              <div style="color:#FFFFFF;font-size:20px;font-weight:700;">' . $safeTitle . '</div>
            </td>
          </tr>
          <tr>
            <td style="padding:20px 32px 0;">
              <span style="display:inline-block;background:#DCEBFA;color:#0F4C81;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;padding:5px 12px;border-radius:999px;">' . $badge . '</span>
              <span style="color:#475467;font-size:13px;margin-left:10px;">Submitted ' . $submittedAt . '</span>
            </td>
          </tr>
          <tr>
            <td style="padding:24px 32px 8px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">' . $rows . '
              </table>
            </td>
          </tr>
          <tr>
            <td style="background:#F7F9FB;padding:16px 32px;border-top:1px solid #EDF0F3;">
              <div style="color:#98A2B3;font-size:11px;">Automated notification from keystoneprep.org &mdash; do not reply to this email. <a href="' . htmlspecialchars($airtableUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#98A2B3;">View in Airtable &rarr;</a></div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

// ── READ INPUT ──────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['form_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing form_type']);
    exit;
}

$formType = $input['form_type'];
$data     = $input['data'] ?? [];

if (!isset($TABLES[$formType])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown form type']);
    exit;
}

// ── SPAM CHECKS ────────────────────────────────────────

// Honeypot: reject if hidden fields were filled
if (!empty($data['_name_confirm']) || !empty($data['_email_confirm'])) {
    echo json_encode(['success' => true]);
    exit;
}

// Timing: reject if submitted faster than 3 seconds
if (!empty($input['_form_loaded'])) {
    $elapsed = (microtime(true) * 1000 - floatval($input['_form_loaded'])) / 1000;
    if ($elapsed < 3) {
        echo json_encode(['success' => true]);
        exit;
    }
}

// reCAPTCHA v3 verification
$recaptchaToken = $input['_recaptcha_token'] ?? '';
if ($recaptchaToken) {
    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $rcch = curl_init($recaptchaUrl);
    curl_setopt_array($rcch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET,
            'response' => $recaptchaToken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
    ]);
    $rcResponse = json_decode(curl_exec($rcch), true);
    curl_close($rcch);

    if (!empty($rcResponse['success']) && isset($rcResponse['score'])) {
        if ($rcResponse['score'] < RECAPTCHA_THRESHOLD) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

// Clean spam-check fields from data before processing
unset($data['_name_confirm'], $data['_email_confirm']);

// ── BUILD AIRTABLE FIELDS ───────────────────────────────
$fieldMap = $FIELD_MAPS[$formType] ?? [];
$fields   = [];

// Handle combined name fields
if (isset($data['first_name']) && isset($data['last_name'])) {
    $combinedName = trim($data['first_name'] . ' ' . $data['last_name']);
    if ($formType === 'misconduct') {
        $fields['Reporter Name'] = $combinedName;
    } else {
        $fields['Name'] = $combinedName;
    }
}

// Handle combined student name for applications
if ($formType === 'application' && isset($data['student_first']) && isset($data['student_last'])) {
    $fields['Student Name'] = trim($data['student_first'] . ' ' . $data['student_last']);
}

// Handle combined address for applications
if ($formType === 'application') {
    $addrParts = array_filter([
        $data['home_street'] ?? '',
        $data['home_city'] ?? '',
        $data['home_state'] ?? '',
        $data['home_zip'] ?? '',
    ]);
    if ($addrParts) {
        $fields['Address'] = implode(', ', $addrParts);
    }
}

// Map remaining fields
foreach ($data as $key => $value) {
    if (empty($value)) continue;
    if (!isset($fieldMap[$key])) continue;
    $target = $fieldMap[$key];
    if (strpos($target, '_combine') === 0) continue;
    if (!isset($fields[$target])) {
        $fields[$target] = $value;
    } else if ($target === 'Learning Details') {
        $fields[$target] .= "\n" . $value;
    }
}

$fields['Status'] = 'New';
$fields['Submitted At'] = date('c');

// ── POST TO AIRTABLE ────────────────────────────────────
$tableId  = $TABLES[$formType];
$url      = "https://api.airtable.com/v0/" . AIRTABLE_BASE . "/$tableId";
$payload  = json_encode(['records' => [['fields' => $fields]], 'typecast' => true]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . AIRTABLE_TOKEN,
        'Content-Type: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$airtableOk = ($httpCode >= 200 && $httpCode < 300);

// ── SEND EMAIL NOTIFICATION VIA RESEND ──────────────────
$submitterName = $fields['Name'] ?? $fields['Student Name'] ?? $fields['Parent Name'] ?? $fields['Reporter Name'] ?? '';
$subject = '[KSPHS] ' . ($EMAIL_SUBJECTS[$formType] ?? 'New Form Submission') . ($submitterName ? ' - ' . $submitterName : ' - ' . date('m/d/y g:iA'));
$body    = "A new form submission has been received.\n\n";
$body   .= "Form: " . ucfirst($formType) . "\n";
$body   .= "Submitted: " . date('M j, Y g:i A T') . "\n";
$body   .= str_repeat('-', 40) . "\n\n";

foreach ($fields as $label => $val) {
    if ($label === 'Status' || $label === 'Submitted At') continue;
    $body .= "$label: $val\n";
}

$body .= "\n" . str_repeat('-', 40) . "\n";
$airtableUrl = "https://airtable.com/" . AIRTABLE_BASE . "/$tableId";
$body .= "View in Airtable: $airtableUrl\n";

$replyTo = $fields['Email'] ?? $fields['Parent Email'] ?? $fields['Parent 1 Email'] ?? $fields['Reporter Email'] ?? FROM_EMAIL;

$badgeLabel = ucwords(str_replace('_', ' ', $formType));
$htmlBody = build_notification_html($EMAIL_SUBJECTS[$formType] ?? 'New Form Submission', $badgeLabel, $fields, $airtableUrl);

$emailOk = send_resend_email(NOTIFY_EMAIL, $subject, $body, $htmlBody, $replyTo);

// ── GUARDIAN CONFIRMATION EMAIL (application form only) ──
$confirmationSent = false;
if ($formType === 'application' && $airtableOk && !empty($fields['Parent 1 Email'])) {
    $guardianName = $fields['Parent 1 Name'] ?? 'Parent/Guardian';
    $studentName  = $fields['Student Name'] ?? 'your student';

    $confirmSubject = "We've Received {$studentName}'s Application — Keystone Prep High School";
    $confirmBody  = "Dear {$guardianName},\n\n";
    $confirmBody .= "Thank you for submitting an application for {$studentName} to Keystone Prep High School. We've received it and our admissions team will review it shortly.\n\n";
    $confirmBody .= "What happens next:\n";
    $confirmBody .= "- Our admissions team will review your application and typically follow up within 5-7 business days.\n";
    $confirmBody .= "- If you have any supporting documents to share (psychoeducational evaluations, IEPs, 504 plans, transcripts, or therapy records), you can email them to info@keystoneprep.org.\n";
    $confirmBody .= "- Families may be contacted to schedule a tour, interview, or student visit as part of the review.\n\n";
    $confirmBody .= "Questions in the meantime? Reach our admissions team:\n";
    $confirmBody .= "Phone: (813) 264-4500\n";
    $confirmBody .= "Email: info@keystoneprep.org\n\n";
    $confirmBody .= "We look forward to learning more about {$studentName}.\n\n";
    $confirmBody .= "Warmly,\nKeystone Prep High School Admissions Team";

    $confirmationSent = send_resend_email($fields['Parent 1 Email'], $confirmSubject, $confirmBody, null, NOTIFY_EMAIL);
}

// ── RESPONSE ────────────────────────────────────────────
if ($airtableOk) {
    echo json_encode(['success' => true, 'email_sent' => $emailOk, 'confirmation_sent' => $confirmationSent]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save submission', 'airtable_response' => $response]);
}
