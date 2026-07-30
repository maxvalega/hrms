-- Sample data for Payroll Module (company user id = 1)
-- Run after migrations.

INSERT INTO pay_schedule (pay_frequency, pay_day, working_days, start_month, status, is_locked, created_by, created_at, updated_at)
VALUES ('monthly', 27, 'mon,tue,wed,thu,fri,sat', DATE_FORMAT(CURDATE(), '%Y-%m'), 1, 0, 1, NOW(), NOW());

-- Assumes salary_structures id=1 exists (India Standard Structure)
INSERT INTO salary_components
    (name, category, type, calculation_type, value, formula, max_limit, is_taxable, is_pf_applicable, is_esic_applicable, frequency, condition_rule, status, created_by, created_at, updated_at)
VALUES
    ('Basic Salary', 'earning', 'earning', 'percentage', 50.00, 'CTC_ANNUAL', NULL, 1, 1, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('HRA', 'earning', 'earning', 'percentage', 50.00, 'BASIC', NULL, 1, 0, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('Conveyance', 'earning', 'earning', 'fixed', 19200.00, NULL, NULL, 1, 0, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('Medical', 'earning', 'earning', 'fixed', 15000.00, NULL, NULL, 1, 0, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('Special Allowance', 'earning', 'earning', 'formula', NULL, 'MAX(CTC_ANNUAL - (BASIC + HRA + CONVEYANCE + MEDICAL), 0)', NULL, 1, 0, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('PF Employee', 'deduction', 'deduction', 'formula', NULL, 'MIN(BASIC * 0.12, 21600)', NULL, 0, 1, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('ESIC Employee', 'deduction', 'deduction', 'formula', NULL, 'BASIC * 0.0075', NULL, 0, 0, 1, 'monthly', 'GROSS <= 252000', 1, 1, NOW(), NOW()),
    ('Employer PF', 'benefit', 'employer', 'formula', NULL, 'MIN(BASIC * 0.12, 21600)', NULL, 0, 1, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('Gratuity', 'benefit', 'employer', 'formula', NULL, 'BASIC * 0.0481', NULL, 0, 0, 0, 'monthly', NULL, 1, 1, NOW(), NOW()),
    ('Fuel', 'reimbursement', 'earning', 'fixed', 0.00, NULL, 2500.00, 0, 0, 0, 'monthly', NULL, 1, 1, NOW(), NOW());

