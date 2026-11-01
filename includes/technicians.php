<?php

/**
 * Technician Assignment & Workload Validation Module
 * After-Sales Service Information System (ASSIS)
 * 
 * Enforces assignment rules based on:
 * 1. Availability (Must be 'Available', not 'On Leave', not 'Busy')
 * 2. Active workload capacity (Maximum concurrent active jobs limit)
 * 3. Specialization domain compatibility (Product category & fault domain)
 */

define('MAX_TECHNICIAN_ACTIVE_JOBS', 3);

/**
 * Determines the primary technical domain of a product and fault description.
 */
function get_equipment_domain(string $productName, string $issueDescription = ''): string {
    $text = strtolower($productName . ' ' . $issueDescription);

    $domains = [
        'audio_visual' => [
            'tv', 'television', 'audio', 'speaker', 'soundbar', 'display', 'screen',
            'monitor', 'projector', 'video', 'radio', 'amplifier', 'hdmi', 'oled', 'qled', 'led'
        ],
        'refrigeration_cooling' => [
            'refrigerat', 'fridge', 'freezer', 'cooler', 'chiller', 'air condition',
            'ac', 'hvac', 'compressor', 'cooling', 'ice maker', 'thermostat', 'gas leak'
        ],
        'laundry' => [
            'washing machine', 'washer', 'dryer', 'laundry', 'dishwasher', 'spin cycle', 'drum'
        ],
        'kitchen_cooking' => [
            'microwave', 'oven', 'cooker', 'stove', 'blender', 'kettle', 'toaster', 'coffee', 'induction'
        ],
        'computing' => [
            'laptop', 'desktop', 'computer', 'printer', 'scanner', 'pc', 'server', 'ups', 'motherboard'
        ],
    ];

    foreach ($domains as $domain => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return $domain;
            }
        }
    }

    return 'general';
}

/**
 * Maps a technician's specialization string into recognized technical domains.
 */
function get_technician_specialization_domains(string $specialization): array {
    $spec = strtolower($specialization);
    $matched = [];

    $domains = [
        'audio_visual' => ['audio', 'visual', 'sound', 'video', 'tv', 'television', 'entertainment', 'screen', 'display'],
        'refrigeration_cooling' => ['refrigerat', 'fridge', 'freezer', 'cooling', 'chiller', 'hvac', 'air conditioning', 'ac'],
        'laundry' => ['laundry', 'washing', 'washer', 'dryer'],
        'kitchen_cooking' => ['kitchen', 'cooking', 'microwave', 'oven', 'cooker', 'appliance'],
        'computing' => ['comput', 'it', 'laptop', 'hardware', 'electronics', 'software', 'network'],
    ];

    foreach ($domains as $domain => $keywords) {
        foreach ($keywords as $kw) {
            if (str_contains($spec, $kw)) {
                $matched[] = $domain;
                break;
            }
        }
    }

    // Default to 'general' if multi-disciplinary, electrical, or unrecognized
    if (empty($matched) || str_contains($spec, 'general') || str_contains($spec, 'all') || str_contains($spec, 'electrical')) {
        $matched[] = 'general';
    }

    return $matched;
}

/**
 * Checks whether a technician's specialization is compatible with the equipment and fault.
 */
function is_technician_specialization_compatible(string $specialization, string $productName, string $issueDescription = ''): bool {
    $specDomains = get_technician_specialization_domains($specialization);

    // General or multi-disciplinary technicians can service all domains
    if (in_array('general', $specDomains, true)) {
        return true;
    }

    $prodDomain = get_equipment_domain($productName, $issueDescription);

    // Unclassified products can be assigned to any technician
    if ($prodDomain === 'general') {
        return true;
    }

    return in_array($prodDomain, $specDomains, true);
}

/**
 * Validates whether a technician can be assigned to a service request.
 * Returns null if valid, or an error message string if invalid.
 */
function validate_technician_assignment(PDO $db, int $technicianId, int $productId, string $issueDescription = '', ?int $currentRequestId = null): ?string {
    $stmt = $db->prepare('SELECT TechnicianID, FullName, Specialization, Availability FROM TECHNICIAN WHERE TechnicianID = :tid');
    $stmt->execute([':tid' => $technicianId]);
    $tech = $stmt->fetch();

    if (!$tech) {
        return 'The selected technician record does not exist.';
    }

    // 1. Availability Check: On Leave
    if ($tech['Availability'] === 'On Leave') {
        return 'Assignment rejected: Technician ' . e($tech['FullName']) . ' is currently On Leave.';
    }

    // 2. Availability Check: Marked Busy
    if ($tech['Availability'] === 'Busy') {
        return 'Assignment rejected: Technician ' . e($tech['FullName']) . ' is currently marked as Busy.';
    }

    // 3. Active Workload Capacity Check
    $workloadSql = 'SELECT COUNT(*) FROM SERVICE_REQUEST WHERE TechnicianID = :tid AND Status IN (\'Pending\', \'In Progress\')';
    $params = [':tid' => $technicianId];
    if ($currentRequestId) {
        $workloadSql .= ' AND RequestID != :rid';
        $params[':rid'] = $currentRequestId;
    }
    $workloadStmt = $db->prepare($workloadSql);
    $workloadStmt->execute($params);
    $activeJobs = (int)$workloadStmt->fetchColumn();

    if ($activeJobs >= MAX_TECHNICIAN_ACTIVE_JOBS) {
        return 'Assignment rejected: Technician ' . e($tech['FullName']) . ' has reached maximum active capacity (' . $activeJobs . ' active jobs).';
    }

    // 4. Specialization Compatibility Check
    $prodStmt = $db->prepare('SELECT ProductName, Model FROM PRODUCT WHERE ProductID = :pid');
    $prodStmt->execute([':pid' => $productId]);
    $product = $prodStmt->fetch();

    if ($product) {
        $productTitle = $product['ProductName'] . ' ' . $product['Model'];
        if (!is_technician_specialization_compatible($tech['Specialization'], $productTitle, $issueDescription)) {
            return 'Assignment rejected: Technician ' . e($tech['FullName']) . ' specializes in \'' . e($tech['Specialization']) . '\', which does not match the product domain for \'' . e($product['ProductName']) . '\'.';
        }
    }

    return null;
}

/**
 * Synchronizes the technician's Availability column ('Available' vs 'Busy')
 * based on their active workload count. Does not alter 'On Leave' status.
 */
function sync_technician_availability(PDO $db, int $technicianId): void {
    if ($technicianId <= 0) {
        return;
    }

    $stmt = $db->prepare('SELECT Availability FROM TECHNICIAN WHERE TechnicianID = :tid');
    $stmt->execute([':tid' => $technicianId]);
    $currentStatus = $stmt->fetchColumn();

    if (!$currentStatus || $currentStatus === 'On Leave') {
        return;
    }

    $workloadStmt = $db->prepare("SELECT COUNT(*) FROM SERVICE_REQUEST WHERE TechnicianID = :tid AND Status IN ('Pending', 'In Progress')");
    $workloadStmt->execute([':tid' => $technicianId]);
    $activeJobs = (int)$workloadStmt->fetchColumn();

    $newStatus = ($activeJobs >= MAX_TECHNICIAN_ACTIVE_JOBS) ? 'Busy' : 'Available';

    if ($newStatus !== $currentStatus) {
        $update = $db->prepare('UPDATE TECHNICIAN SET Availability = :avail WHERE TechnicianID = :tid');
        $update->execute([':avail' => $newStatus, ':tid' => $technicianId]);
    }
}

