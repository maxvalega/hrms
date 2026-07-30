-- Dynamic Salary Structure Module SQL (CodeIgniter 3)

CREATE TABLE IF NOT EXISTS salary_components (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  type ENUM('earning','deduction','employer') NOT NULL,
  calculation_type ENUM('fixed','percentage','formula') NOT NULL DEFAULT 'fixed',
  value DECIMAL(15,2) NULL,
  formula TEXT NULL,
  condition_rule TEXT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS salary_structure (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  country VARCHAR(60) NOT NULL DEFAULT 'India',
  created_at DATETIME NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS structure_components (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  structure_id INT UNSIGNED NOT NULL,
  component_id INT UNSIGNED NOT NULL,
  priority INT NOT NULL DEFAULT 10,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_structure_components_structure FOREIGN KEY (structure_id) REFERENCES salary_structure(id) ON DELETE CASCADE,
  CONSTRAINT fk_structure_components_component FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE CASCADE,
  UNIQUE KEY uq_structure_component (structure_id, component_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS employee_salary (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employee_id INT UNSIGNED NOT NULL,
  ctc DECIMAL(15,2) NOT NULL,
  basic_percentage DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  is_pf_enabled TINYINT(1) NOT NULL DEFAULT 1,
  is_esic_enabled TINYINT(1) NOT NULL DEFAULT 1,
  structure_id INT UNSIGNED NULL,
  created_at DATETIME NULL,
  updated_at DATETIME NULL,
  INDEX idx_employee_salary_employee_id (employee_id),
  CONSTRAINT fk_employee_salary_structure FOREIGN KEY (structure_id) REFERENCES salary_structure(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO salary_structure (name, country, created_at, updated_at)
SELECT 'India Standard Structure', 'India', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_structure WHERE name = 'India Standard Structure');

-- Correct duplicate check for each component insert
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Basic', 'earning', 'percentage', 50.00, 'CTC_ANNUAL', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Basic');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'HRA', 'earning', 'percentage', 50.00, 'BASIC', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'HRA');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Conveyance', 'earning', 'fixed', 19200.00, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Conveyance');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Medical', 'earning', 'fixed', 40000.00, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Medical');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Dearness Allowance', 'earning', 'fixed', 0.00, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Dearness Allowance');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'City Compensatory Allowance', 'earning', 'fixed', 0.00, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'City Compensatory Allowance');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Other Allowance', 'earning', 'fixed', 0.00, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Other Allowance');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Special Allowance', 'earning', 'formula', NULL, 'MAX(CTC_ANNUAL - (BASIC + HRA + CONVEYANCE + MEDICAL + DEARNESS_ALLOWANCE + CITY_COMPENSATORY_ALLOWANCE + OTHER_ALLOWANCE), 0)', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Special Allowance');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Gross', 'earning', 'formula', NULL, 'BASIC + HRA + CONVEYANCE + MEDICAL + DEARNESS_ALLOWANCE + CITY_COMPENSATORY_ALLOWANCE + OTHER_ALLOWANCE + SPECIAL_ALLOWANCE', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Gross');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'PF Employee', 'deduction', 'formula', NULL, 'MIN(BASIC * 0.12, 21600)', '(BASIC <= 180000) OR (PF_ENABLED == 1)', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'PF Employee');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'ESIC Employee', 'deduction', 'formula', NULL, 'BASIC * 0.0075', '(GROSS <= 252000) AND (ESIC_ENABLED == 1)', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'ESIC Employee');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'ESIC Employer', 'employer', 'formula', NULL, 'BASIC * 0.0325', '(GROSS <= 252000) AND (ESIC_ENABLED == 1)', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'ESIC Employer');
INSERT INTO salary_components (name, type, calculation_type, value, formula, condition_rule, status, created_at, updated_at)
SELECT 'Gratuity', 'employer', 'formula', NULL, 'BASIC * 0.0481', NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM salary_components WHERE name = 'Gratuity');

INSERT IGNORE INTO structure_components (structure_id, component_id, priority, created_at, updated_at)
SELECT ss.id, sc.id,
  CASE
    WHEN sc.name = 'Basic' THEN 10
    WHEN sc.name = 'HRA' THEN 20
    WHEN sc.name = 'Conveyance' THEN 30
    WHEN sc.name = 'Medical' THEN 40
    WHEN sc.name = 'Dearness Allowance' THEN 45
    WHEN sc.name = 'City Compensatory Allowance' THEN 46
    WHEN sc.name = 'Other Allowance' THEN 47
    WHEN sc.name = 'Special Allowance' THEN 50
    WHEN sc.name = 'Gross' THEN 60
    WHEN sc.name = 'PF Employee' THEN 70
    WHEN sc.name = 'ESIC Employee' THEN 80
    WHEN sc.name = 'ESIC Employer' THEN 90
    WHEN sc.name = 'Gratuity' THEN 100
    ELSE 999
  END AS priority,
  NOW(), NOW()
FROM salary_structure ss
JOIN salary_components sc ON 1=1
WHERE ss.name = 'India Standard Structure';
