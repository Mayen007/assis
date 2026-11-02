USE `assis_db`;

ALTER TABLE
    `INVOICE`
ADD
    COLUMN `WarrantyApplied` TINYINT(1) NOT NULL DEFAULT 0
AFTER
    `AmountDue`;

ALTER TABLE
    `INVOICE`
MODIFY
    COLUMN `PaymentStatus` ENUM('Unpaid', 'Paid', 'Partially Paid', 'No Charge') NOT NULL DEFAULT 'Unpaid';