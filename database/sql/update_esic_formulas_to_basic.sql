-- Update existing ESIC component formulas from Gross to Basic.
-- Eligibility condition (GROSS <= 252000 / Gross ≤ ₹21K/mo) is unchanged.
-- Run on live if migration cannot be applied.

UPDATE salary_components
SET formula = 'BASIC * 0.0075', updated_at = NOW()
WHERE name = 'ESIC Employee' AND formula = 'GROSS * 0.0075';

UPDATE salary_components
SET formula = 'BASIC * 0.0325', updated_at = NOW()
WHERE name = 'ESIC Employer' AND formula = 'GROSS * 0.0325';
