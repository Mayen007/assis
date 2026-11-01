<?php

/**
 * Technician Assignment & Workload Validation Module
 * After-Sales Service Information System (ASSIS)
 * 
 * Enforces assignment rules based on:
 * 1. Availability (Must be 'Available', not 'On Leave', not 'Busy')
 * 2. Active workload capacity (Maximum concurrent active jobs limit)
 * 3. Specialization domain compatibility (Product category & fault domain)
 *
 * Capacity checks and the assignment writes they guard must run in the same
 * transaction while holding an exclusive lock on the TECHNICIAN row, otherwise
 * two concurrent assignments can both pass the capacity check.
 */

define('MAX_TECHNICIAN_ACTIVE_JOBS', 3);

/**
 * Opens a transaction for a technician assignment.
 *
 * READ COMMITTED is used so the capacity COUNT() is evaluated against the
 * latest committed rows rather than a REPEATABLE READ snapshot that may have
 * been taken before the technician row lock was acquired.
 */
function begin_assignment_transaction(PDO $db): void {
    if ($db->inTransaction()) {
        throw new RuntimeException('A technician assignment transaction is already active.');
    }

    $db->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $db->beginTransaction();
}

/**
 * Takes an exclusive lock on the given TECHNICIAN rows until the surrounding
 * transaction ends. Assignment validation locks the target technician, while
 * reassignments lock both technicians in one consistent order.
 *
 * Rows are locked in ascending ID order so a reassignment (which locks both the
 * previous and the new technician) cannot deadlock against another one.
 */
function lock_technician_workload(PDO $db, array $technicianIds): void {
    if (!$db->inTransaction()) {
        throw new LogicException('Technician workload locks require an active transaction.');
    }

    $ids = array_values(array_unique(array_filter(
        array_map('intval', $technicianIds),
        static fn(int $id): bool => $id > 0
    )));

    if (empty($ids)) {
        return;
    }

    sort($ids);
    $stmt = $db->prepare('SELECT TechnicianID FROM TECHNICIAN WHERE TechnicianID = ? FOR UPDATE');
    foreach ($ids as $technicianId) {
        $stmt->execute([$technicianId]);
        $stmt->fetchColumn();
    }
}

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
 *
 * The workload count is only authoritative inside a transaction. The target
 * technician row is locked before checking availability or active workload.
 */
function validate_technician_assignment(PDO $db, int $technicianId, int $productId, string $issueDescription = '', ?int $currentRequestId = null): ?string {
    if (!$db->inTransaction()) {
        throw new LogicException('Technician assignment validation requires an active transaction.');
    }

    lock_technician_workload($db, [$technicianId]);

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
 * Assigns (or reassigns) a technician to an existing service request.
 *
 * The request row and the affected technician rows are locked inside a single
 * transaction, so the capacity check in validate_technician_assignment() and
 * the UPDATE that consumes a slot cannot interleave with a competing assignment.
 *
 * Returns null on success, or an error message if the assignment was rejected.
 */
function assign_technician_to_request(PDO $db, int $requestId, int $technicianId): ?string {
    begin_assignment_transaction($db);

    try {
        // Lock the ticket first, then the technicians. Completion (workbench)
        // locks the request before updating TECHNICIAN, so the order is shared.
        $stmt = $db->prepare('SELECT RequestID, TechnicianID, ProductID, IssueDescription, Status FROM SERVICE_REQUEST WHERE RequestID = :rid FOR UPDATE');
        $stmt->execute([':rid' => $requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            $db->rollBack();
            return 'The service request no longer exists.';
        }

        if (in_array($request['Status'], ['Completed', 'Closed'], true)) {
            $db->rollBack();
            return 'Technician assignment cannot be changed after a request is completed or closed.';
        }

        $previousTechnicianId = (int)$request['TechnicianID'];

        lock_technician_workload($db, [$previousTechnicianId, $technicianId]);

        $assignmentError = validate_technician_assignment(
            $db,
            $technicianId,
            (int)$request['ProductID'],
            (string)$request['IssueDescription'],
            $requestId
        );

        if ($assignmentError !== null) {
            $db->rollBack();
            return $assignmentError;
        }

        $update = $db->prepare('
            UPDATE SERVICE_REQUEST
            SET TechnicianID = :tid, Status = CASE WHEN Status = \'Pending\' THEN \'In Progress\' ELSE Status END
            WHERE RequestID = :rid
        ');
        $update->execute([':tid' => $technicianId, ':rid' => $requestId]);

        sync_technician_availability($db, $technicianId);
        if ($previousTechnicianId > 0 && $previousTechnicianId !== $technicianId) {
            sync_technician_availability($db, $previousTechnicianId);
        }

        $db->commit();
        return null;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
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
