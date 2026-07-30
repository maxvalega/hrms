<?php

namespace App\Http\Controllers;

use App\Models\SalaryComponent;
use App\Models\SalaryStructure;
use App\Models\StructureComponent;
use App\Services\SalaryCalculatorService;
use Illuminate\Http\Request;

class SalaryStructureController extends Controller
{
    public function index()
    {
        if (!\Auth::user()->can('Manage Set Salary')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $creatorId = \Auth::user()->creatorId();
        $this->bootstrapDefaults($creatorId);
        $structures = SalaryStructure::where('created_by', $creatorId)->get();
        $components = SalaryComponent::where('created_by', $creatorId)->orderBy('id')->get();

        return view('salary_structure.index', compact('structures', 'components'));
    }

    public function calculate(Request $request, SalaryCalculatorService $calculator)
    {
        if (!\Auth::check()) {
            return redirect()->route('login');
        }

        $creatorId = \Auth::user()->creatorId();
        $this->bootstrapDefaults($creatorId);
        $structures = SalaryStructure::where('created_by', $creatorId)->get();
        $result = null;

        if ($request->isMethod('post')) {
            $request->validate([
                'ctc' => 'required|numeric|min:0',
                'basic_percentage' => 'required|numeric|min:0|max:100',
                'structure_id' => 'required|integer',
            ]);

            $structureId = (int)$request->structure_id;
            $components = SalaryComponent::query()
                ->join('structure_components as sc_map', 'sc_map.component_id', '=', 'salary_components.id')
                ->where('sc_map.structure_id', $structureId)
                ->where('salary_components.created_by', $creatorId)
                ->orderBy('sc_map.priority')
                ->get(['salary_components.*'])
                ->toArray();

            $result = $calculator->calculate(
                [
                    'ctc' => (float)$request->ctc,
                    'basic_percentage' => (float)$request->basic_percentage,
                    'is_pf_enabled' => $request->has('is_pf_enabled') ? 1 : 0,
                    'is_esic_enabled' => $request->has('is_esic_enabled') ? 1 : 0,
                ],
                $components
            );

            if ($request->ajax()) {
                return response()->json($result);
            }
        }

        return view('salary_structure.calculate', compact('result', 'structures'));
    }

    public function storeComponent(Request $request)
    {
        if (!\Auth::user()->can('Manage Set Salary')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:earning,deduction,employer',
            'calculation_type' => 'required|in:fixed,percentage,formula',
        ]);

        $component = SalaryComponent::create([
            'name' => $request->name,
            'type' => $request->type,
            'calculation_type' => $request->calculation_type,
            'value' => $request->value !== null ? (float)$request->value : null,
            'formula' => $request->formula,
            'condition_rule' => $request->condition_rule,
            'status' => $request->has('status') ? 1 : 0,
            'created_by' => \Auth::user()->creatorId(),
        ]);

        $structureId = (int)$request->structure_id;
        if ($structureId > 0) {
            StructureComponent::updateOrCreate(
                ['structure_id' => $structureId, 'component_id' => $component->id],
                ['priority' => (int)($request->priority ?? 999)]
            );
        }

        return redirect()->route('salary.structure.index')->with('success', __('Salary component created.'));
    }

    protected function bootstrapDefaults(int $creatorId): void
    {
        $structure = SalaryStructure::firstOrCreate(
            ['name' => 'India Standard Structure', 'created_by' => $creatorId],
            ['country' => 'India']
        );

        $defaults = [
            // Earnings (calculated first)
            ['name' => 'Basic', 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 50, 'formula' => 'CTC_ANNUAL', 'condition_rule' => null, 'priority' => 10],
            ['name' => 'HRA', 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 50, 'formula' => 'BASIC', 'condition_rule' => null, 'priority' => 20],
            ['name' => 'Conveyance', 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 19200, 'formula' => null, 'condition_rule' => null, 'priority' => 30],
            ['name' => 'Medical', 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 40000, 'formula' => null, 'condition_rule' => null, 'priority' => 40],
            // Employer contributions (calculated before Special Allowance so it can be deducted from CTC)
            ['name' => 'PF Employer', 'type' => 'employer', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'MIN(BASIC * 0.12, 21600)', 'condition_rule' => '(BASIC <= 180000) OR (PF_ENABLED == 1)', 'priority' => 48],
            ['name' => 'Gratuity', 'type' => 'employer', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'BASIC * 0.0481', 'condition_rule' => null, 'priority' => 49],
            // Special Allowance = CTC minus all fixed earnings minus employer contributions
            ['name' => 'Special Allowance', 'type' => 'earning', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'MAX(CTC_ANNUAL - (BASIC + HRA + CONVEYANCE + MEDICAL + PF_EMPLOYER + GRATUITY), 0)', 'condition_rule' => null, 'priority' => 50],
            ['name' => 'Gross', 'type' => 'earning', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'BASIC + HRA + CONVEYANCE + MEDICAL + SPECIAL_ALLOWANCE', 'condition_rule' => null, 'priority' => 60],
            // Employee deductions
            ['name' => 'PF Employee', 'type' => 'deduction', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'MIN(BASIC * 0.12, 21600)', 'condition_rule' => '(BASIC <= 180000) OR (PF_ENABLED == 1)', 'priority' => 70],
            ['name' => 'ESIC Employee', 'type' => 'deduction', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'BASIC * 0.0075', 'condition_rule' => '(GROSS <= 252000) AND (ESIC_ENABLED == 1)', 'priority' => 80],
            ['name' => 'ESIC Employer', 'type' => 'employer', 'calculation_type' => 'formula', 'value' => null, 'formula' => 'BASIC * 0.0325', 'condition_rule' => '(GROSS <= 252000) AND (ESIC_ENABLED == 1)', 'priority' => 90],
        ];

        foreach ($defaults as $item) {
            $component = SalaryComponent::updateOrCreate(
                ['name' => $item['name'], 'created_by' => $creatorId],
                [
                    'type' => $item['type'],
                    'calculation_type' => $item['calculation_type'],
                    'value' => $item['value'],
                    'formula' => $item['formula'],
                    'condition_rule' => $item['condition_rule'],
                    'status' => 1,
                ]
            );

            StructureComponent::updateOrCreate(
                ['structure_id' => $structure->id, 'component_id' => $component->id],
                ['priority' => $item['priority']]
            );
        }
    }
}

