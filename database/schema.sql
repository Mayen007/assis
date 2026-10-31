-- =====================================================================
-- After-Sales Service Information System (ASSIS)
-- Database Schema Definition (MySQL)
-- Reference: sameera.md (Chapter Five, Section 5.2.3)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `assis_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `assis_db`;

-- 1. TECHNICIAN Table (Table 5.5)
CREATE TABLE IF NOT EXISTS `TECHNICIAN` (
    `TechnicianID` INT AUTO_INCREMENT PRIMARY KEY,
    `FullName` VARCHAR(100) NOT NULL,
    `Specialization` VARCHAR(100) NOT NULL,
    `Phone` VARCHAR(20) NULL,
    `Availability` ENUM('Available', 'Busy', 'On Leave') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB;

-- 2. USERS Table (System Authentication & Role-Based Access Control)
CREATE TABLE IF NOT EXISTS `USERS` (
    `UserID` INT AUTO_INCREMENT PRIMARY KEY,
    `Username` VARCHAR(50) NOT NULL UNIQUE,
    `PasswordHash` VARCHAR(255) NOT NULL,
    `FullName` VARCHAR(100) NOT NULL,
    `Role` ENUM('Admin', 'FrontDesk', 'Technician') NOT NULL,
    `RelatedTechnicianID` INT NULL,
    `IsActive` TINYINT(1) NOT NULL DEFAULT 1,
    `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_technician` FOREIGN KEY (`RelatedTechnicianID`) REFERENCES `TECHNICIAN` (`TechnicianID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. CUSTOMER Table (Table 5.2)
CREATE TABLE IF NOT EXISTS `CUSTOMER` (
    `CustomerID` INT AUTO_INCREMENT PRIMARY KEY,
    `FullName` VARCHAR(100) NOT NULL,
    `PhoneNumber` VARCHAR(20) NOT NULL UNIQUE,
    `Email` VARCHAR(100) NULL,
    `PhysicalAddress` VARCHAR(150) NULL,
    `DateRegistered` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. PRODUCT Table (Table 5.3)
CREATE TABLE IF NOT EXISTS `PRODUCT` (
    `ProductID` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerID` INT NOT NULL,
    `ProductName` VARCHAR(100) NOT NULL,
    `Model` VARCHAR(100) NOT NULL,
    `SerialNumber` VARCHAR(100) NOT NULL UNIQUE,
    `PurchaseDate` DATE NULL,
    CONSTRAINT `fk_product_customer` FOREIGN KEY (`CustomerID`) REFERENCES `CUSTOMER` (`CustomerID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. WARRANTY Table (Table 5.4)
CREATE TABLE IF NOT EXISTS `WARRANTY` (
    `WarrantyID` INT AUTO_INCREMENT PRIMARY KEY,
    `ProductID` INT NOT NULL,
    `StartDate` DATE NOT NULL,
    `EndDate` DATE NOT NULL,
    `Terms` TEXT NULL,
    CONSTRAINT `fk_warranty_product` FOREIGN KEY (`ProductID`) REFERENCES `PRODUCT` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. SERVICE_REQUEST Table (Table 5.6)
CREATE TABLE IF NOT EXISTS `SERVICE_REQUEST` (
    `RequestID` INT AUTO_INCREMENT PRIMARY KEY,
    `CustomerID` INT NOT NULL,
    `ProductID` INT NOT NULL,
    `TechnicianID` INT NULL,
    `DateLogged` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `IssueDescription` TEXT NOT NULL,
    `Priority` ENUM('Low', 'Medium', 'High') NOT NULL DEFAULT 'Medium',
    `Status` ENUM('Pending', 'In Progress', 'Completed', 'Closed') NOT NULL DEFAULT 'Pending',
    CONSTRAINT `fk_request_customer` FOREIGN KEY (`CustomerID`) REFERENCES `CUSTOMER` (`CustomerID`),
    CONSTRAINT `fk_request_product` FOREIGN KEY (`ProductID`) REFERENCES `PRODUCT` (`ProductID`),
    CONSTRAINT `fk_request_technician` FOREIGN KEY (`TechnicianID`) REFERENCES `TECHNICIAN` (`TechnicianID`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. SERVICE_RECORD Table (Table 5.7)
CREATE TABLE IF NOT EXISTS `SERVICE_RECORD` (
    `RecordID` INT AUTO_INCREMENT PRIMARY KEY,
    `RequestID` INT NOT NULL UNIQUE,
    `ActionsTaken` TEXT NOT NULL,
    `CompletionDate` DATE NULL,
    `LabourCost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT `fk_record_request` FOREIGN KEY (`RequestID`) REFERENCES `SERVICE_REQUEST` (`RequestID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. SPARE_PART Table (Table 5.8)
CREATE TABLE IF NOT EXISTS `SPARE_PART` (
    `PartID` INT AUTO_INCREMENT PRIMARY KEY,
    `PartName` VARCHAR(100) NOT NULL,
    `QuantityInStock` INT NOT NULL DEFAULT 0,
    `ReorderLevel` INT NOT NULL DEFAULT 5,
    `UnitPrice` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `chk_stock_positive` CHECK (`QuantityInStock` >= 0)
) ENGINE=InnoDB;

-- 9. PARTS_USED Table (Associative Entity, Table 5.9)
CREATE TABLE IF NOT EXISTS `PARTS_USED` (
    `PartsUsedID` INT AUTO_INCREMENT PRIMARY KEY,
    `RecordID` INT NOT NULL,
    `PartID` INT NOT NULL,
    `QuantityUsed` INT NOT NULL,
    CONSTRAINT `fk_partsused_record` FOREIGN KEY (`RecordID`) REFERENCES `SERVICE_RECORD` (`RecordID`) ON DELETE CASCADE,
    CONSTRAINT `fk_partsused_part` FOREIGN KEY (`PartID`) REFERENCES `SPARE_PART` (`PartID`),
    CONSTRAINT `chk_qty_positive` CHECK (`QuantityUsed` > 0)
) ENGINE=InnoDB;

-- 10. INVOICE Table (Table 5.10)
CREATE TABLE IF NOT EXISTS `INVOICE` (
    `InvoiceID` INT AUTO_INCREMENT PRIMARY KEY,
    `RequestID` INT NOT NULL UNIQUE,
    `AmountDue` DECIMAL(10,2) NOT NULL,
    `PaymentStatus` ENUM('Unpaid', 'Paid', 'Partially Paid') NOT NULL DEFAULT 'Unpaid',
    `DateIssued` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_invoice_request` FOREIGN KEY (`RequestID`) REFERENCES `SERVICE_REQUEST` (`RequestID`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. FEEDBACK Table (Table 5.11)
CREATE TABLE IF NOT EXISTS `FEEDBACK` (
    `FeedbackID` INT AUTO_INCREMENT PRIMARY KEY,
    `RequestID` INT NOT NULL UNIQUE,
    `Rating` INT NOT NULL,
    `Comments` TEXT NULL,
    `FeedbackDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_feedback_request` FOREIGN KEY (`RequestID`) REFERENCES `SERVICE_REQUEST` (`RequestID`) ON DELETE CASCADE,
    CONSTRAINT `chk_rating_range` CHECK (`Rating` >= 1 AND `Rating` <= 5)
) ENGINE=InnoDB;
