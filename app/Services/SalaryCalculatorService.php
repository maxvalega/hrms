<?php

namespace App\Services;

use Exception;

class SalaryCalculatorService
{
    public function calculate(array $input, array $components): array
    {
        $ctc = (float)($input['ctc'] ?? 0);
        $basicPercentage = (float)($input['basic_percentage'] ?? 50);
        $pfEnabled = !empty($input['is_pf_enabled']) ? 1 : 0;
        $esicEnabled = !empty($input['is_esic_enabled']) ? 1 : 0;

        $context = [
            'CTC_ANNUAL' => $ctc,
            'CTC_MONTHLY' => $ctc / 12,
            'BASIC_PERCENTAGE' => $basicPercentage,
            'PF_ENABLED' => $pfEnabled,
            'ESIC_ENABLED' => $esicEnabled,
        ];

        $breakdown = [];
        $earnings = 0.0;
        $deductions = 0.0;
        $employer = 0.0;

        foreach ($components as $component) {
            if ((int)($component['status'] ?? 0) !== 1) {
                continue;
            }

            $var = $this->toVar($component['name'] ?? '');

            if (!$this->passesCondition($component['condition_rule'] ?? null, $context)) {
                $amount = 0.0;
            } else {
                $amount = $this->resolveAmount($component, $context, $ctc, $basicPercentage);
            }

            $amount = round(max($amount, 0), 2);
            $breakdown[$var] = $amount;
            $context[$var] = $amount;

            if (($component['type'] ?? '') === 'earning') {
                $earnings += $amount;
            } elseif (($component['type'] ?? '') === 'deduction') {
                $deductions += $amount;
            } elseif (($component['type'] ?? '') === 'employer') {
                $employer += $amount;
            }
        }

        if (!isset($breakdown['GROSS'])) {
            $breakdown['GROSS'] = round($earnings, 2);
        }

        $netAnnual = $breakdown['GROSS'] - $deductions;
        $annual = [
            'basic' => (float)($breakdown['BASIC'] ?? 0),
            'hra' => (float)($breakdown['HRA'] ?? 0),
            'conveyance' => (float)($breakdown['CONVEYANCE'] ?? 0),
            'medical' => (float)($breakdown['MEDICAL'] ?? 0),
            'special' => (float)($breakdown['SPECIAL_ALLOWANCE'] ?? 0),
            'gross' => (float)($breakdown['GROSS'] ?? 0),
            'pf' => (float)($breakdown['PF_EMPLOYEE'] ?? 0),
            'esic_employee' => (float)($breakdown['ESIC_EMPLOYEE'] ?? 0),
            'esic_employer' => (float)($breakdown['ESIC_EMPLOYER'] ?? 0),
            'gratuity' => (float)($breakdown['GRATUITY'] ?? 0),
            'net_salary' => round($netAnnual, 2),
            'components' => $breakdown,
        ];

        return [
            'annual' => $annual,
            'monthly' => $this->toMonthly($annual),
            'meta' => [
                'ctc_annual' => round($ctc, 2),
                'ctc_monthly' => round($ctc / 12, 2),
                'basic_percentage' => $basicPercentage,
                'total_earnings_annual' => round($earnings, 2),
                'total_deductions_annual' => round($deductions, 2),
                'total_employer_contribution_annual' => round($employer, 2),
            ],
        ];
    }

    protected function toMonthly(array $annual): array
    {
        $monthly = [];
        foreach ($annual as $key => $value) {
            if ($key === 'components') {
                $monthly['components'] = [];
                foreach ($value as $k => $amount) {
                    $monthly['components'][strtolower($k)] = round(((float)$amount) / 12, 2);
                }
                continue;
            }
            $monthly[$key] = round(((float)$value) / 12, 2);
        }
        return $monthly;
    }

    protected function resolveAmount(array $component, array $context, float $ctc, float $basicPercentage): float
    {
        $type = $component['calculation_type'] ?? 'fixed';
        $value = (float)($component['value'] ?? 0);
        $formula = trim((string)($component['formula'] ?? ''));

        if ($type === 'fixed') {
            return $value;
        }

        if ($type === 'percentage') {
            $base = $formula !== '' ? $this->evaluateExpression($formula, $context) : $ctc;
            if ($this->toVar($component['name'] ?? '') === 'BASIC') {
                return $base * ($basicPercentage / 100);
            }
            return $base * ($value / 100);
        }

        if ($type === 'formula') {
            return $this->evaluateExpression($formula, $context);
        }

        return 0;
    }

    protected function passesCondition(?string $rule, array $context): bool
    {
        $rule = trim((string)$rule);
        if ($rule === '') {
            return true;
        }
        try {
            return (bool)$this->evaluateExpression($rule, $context);
        } catch (Exception $e) {
            return false;
        }
    }

    protected function toVar(string $name): string
    {
        $v = strtoupper(trim($name));
        $v = preg_replace('/[^A-Z0-9]+/', '_', $v);
        return trim((string)$v, '_');
    }

    protected function evaluateExpression(string $expression, array $context): float
    {
        $tokens = $this->tokenize($expression);
        $rpn = $this->toRpn($tokens);
        return $this->evalRpn($rpn, $context);
    }

    protected function tokenize(string $expression): array
    {
        $pattern = '/\s*(\d+\.\d+|\d+|==|!=|<=|>=|<|>|\bAND\b|\bOR\b|[A-Za-z_][A-Za-z0-9_]*|[\+\-\*\/\(\),])\s*/i';
        preg_match_all($pattern, $expression, $matches);
        return $matches[1] ?? [];
    }

    protected function toRpn(array $tokens): array
    {
        $output = [];
        $ops = [];
        $argcStack = [];
        $precedence = ['OR' => 1, 'AND' => 2, '==' => 3, '!=' => 3, '<' => 3, '<=' => 3, '>' => 3, '>=' => 3, '+' => 4, '-' => 4, '*' => 5, '/' => 5];
        $functions = ['MIN', 'MAX', 'ROUND', 'IF'];

        foreach ($tokens as $i => $token) {
            $upper = strtoupper($token);
            $next = $tokens[$i + 1] ?? null;

            if (is_numeric($token)) {
                $output[] = (float)$token;
                continue;
            }

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $token) && !in_array($upper, $functions, true) && !isset($precedence[$upper])) {
                $output[] = ['var' => $upper];
                continue;
            }

            if (in_array($upper, $functions, true) && $next === '(') {
                $ops[] = $upper;
                continue;
            }

            if ($token === ',') {
                while (!empty($ops) && end($ops) !== '(') {
                    $output[] = array_pop($ops);
                }
                if (!empty($argcStack)) {
                    $argcStack[count($argcStack) - 1]++;
                }
                continue;
            }

            if ($token === '(') {
                $ops[] = '(';
                $prev = $tokens[$i - 1] ?? null;
                if ($prev && in_array(strtoupper($prev), $functions, true)) {
                    $argcStack[] = 1;
                }
                continue;
            }

            if ($token === ')') {
                while (!empty($ops) && end($ops) !== '(') {
                    $output[] = array_pop($ops);
                }
                array_pop($ops);
                if (!empty($ops) && in_array(end($ops), $functions, true)) {
                    $fn = array_pop($ops);
                    $argc = array_pop($argcStack);
                    $output[] = ['fn' => $fn, 'argc' => (int)$argc];
                }
                continue;
            }

            if (isset($precedence[$upper])) {
                while (!empty($ops) && isset($precedence[strtoupper((string)end($ops))]) && $precedence[strtoupper((string)end($ops))] >= $precedence[$upper]) {
                    $output[] = array_pop($ops);
                }
                $ops[] = $upper;
            }
        }

        while (!empty($ops)) {
            $output[] = array_pop($ops);
        }

        return $output;
    }

    protected function evalRpn(array $rpn, array $context): float
    {
        $stack = [];
        foreach ($rpn as $token) {
            if (is_float($token) || is_int($token)) {
                $stack[] = (float)$token;
                continue;
            }

            if (is_array($token) && isset($token['var'])) {
                $stack[] = (float)($context[$token['var']] ?? 0);
                continue;
            }

            if (is_array($token) && isset($token['fn'])) {
                $argc = (int)$token['argc'];
                $args = [];
                for ($i = 0; $i < $argc; $i++) {
                    array_unshift($args, (float)array_pop($stack));
                }
                $stack[] = $this->applyFunction($token['fn'], $args);
                continue;
            }

            $b = (float)array_pop($stack);
            $a = (float)array_pop($stack);
            $stack[] = $this->applyOperator((string)$token, $a, $b);
        }

        return (float)($stack[0] ?? 0);
    }

    protected function applyFunction(string $fn, array $args): float
    {
        switch (strtoupper($fn)) {
            case 'MIN':
                return (float)min($args);
            case 'MAX':
                return (float)max($args);
            case 'ROUND':
                return round((float)($args[0] ?? 0), (int)($args[1] ?? 2));
            case 'IF':
                return !empty($args[0]) ? (float)($args[1] ?? 0) : (float)($args[2] ?? 0);
            default:
                return 0.0;
        }
    }

    protected function applyOperator(string $op, float $a, float $b): float
    {
        switch (strtoupper($op)) {
            case '+': return $a + $b;
            case '-': return $a - $b;
            case '*': return $a * $b;
            case '/': return $b == 0 ? 0 : $a / $b;
            case '<': return $a < $b ? 1 : 0;
            case '<=': return $a <= $b ? 1 : 0;
            case '>': return $a > $b ? 1 : 0;
            case '>=': return $a >= $b ? 1 : 0;
            case '==': return $a == $b ? 1 : 0;
            case '!=': return $a != $b ? 1 : 0;
            case 'AND': return (!empty($a) && !empty($b)) ? 1 : 0;
            case 'OR': return (!empty($a) || !empty($b)) ? 1 : 0;
            default: return 0;
        }
    }
}

