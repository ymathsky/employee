-- 13. Create 'deduction_exclusions' table for Global Deduction exceptions
CREATE TABLE IF NOT EXISTS `deduction_exclusions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deduction_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `deduction_id` (`deduction_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
