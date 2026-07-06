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
        'first_name'   => '_combine_name',
        'last_name'    => '_combine_name',
        'email'        => 'Email',
        'phone'        => 'Phone',
        'giving_level' => 'Giving Level',
        'gift_type'    => 'Gift Type',
        'message'      => 'Message',
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
        'learning_challenges'   => 'Learning Details',
        'learning_details'      => 'Learning Details',
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
        'reunion'         => 'Reunion',
        'message'         => 'Notes',
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
];

// ── ANTI-SPAM CONFIG ───────────────────────────────────
define('RECAPTCHA_THRESHOLD', 0.5);

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
$body .= "View in Airtable: https://airtable.com/" . AIRTABLE_BASE . "/$tableId\n";

$replyTo = $fields['Email'] ?? $fields['Parent Email'] ?? $fields['Parent 1 Email'] ?? $fields['Reporter Email'] ?? FROM_EMAIL;

$emailPayload = json_encode([
    'from'     => FROM_NAME . ' <' . FROM_EMAIL . '>',
    'to'       => [NOTIFY_EMAIL],
    'reply_to' => $replyTo,
    'subject'  => $subject,
    'text'     => $body,
]);

$mch = curl_init('https://api.resend.com/emails');
curl_setopt_array($mch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_POSTFIELDS     => $emailPayload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . RESEND_API_KEY,
        'Content-Type: application/json',
    ],
]);
curl_exec($mch);
$emailHttpCode = curl_getinfo($mch, CURLINFO_HTTP_CODE);
curl_close($mch);

$emailOk = ($emailHttpCode >= 200 && $emailHttpCode < 300);

// ── RESPONSE ────────────────────────────────────────────
if ($airtableOk) {
    echo json_encode(['success' => true, 'email_sent' => $emailOk]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save submission', 'airtable_response' => $response]);
}
