<?php

namespace App\Http\Controllers;

use App\Models\DucumentUpload;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Utility;
use Spatie\Permission\Models\Role;

class DucumentUploadController extends Controller
{
    protected function isHrViewer(): bool
    {
        return in_array(\Auth::user()->type, ['company', 'hr', 'super admin'], true);
    }

    protected function companyUsers()
    {
        // Use employees.user_id (actual login) so assignment matches who can sign in
        return Employee::where('created_by', \Auth::user()->creatorId())
            ->whereNotNull('user_id')
            ->where('user_id', '>', 0)
            ->orderBy('name')
            ->get(['user_id', 'name', 'email'])
            ->unique('user_id')
            ->mapWithKeys(function ($employee) {
                $label = $employee->name;
                if (!empty($employee->email)) {
                    $label .= ' (' . $employee->email . ')';
                }

                return [(int) $employee->user_id => $label];
            });
    }

    public function index()
    {
        $user = \Auth::user();
        $creatorId = $user->creatorId();

        if ($this->isHrViewer()) {
            // HR / company see every company document (including employee uploads)
            $documents = DucumentUpload::where('created_by', $creatorId)
                ->with(['uploader', 'assignedUser'])
                ->latest()
                ->get();
        } else {
            $userId = (int) $user->id;
            $userRole = $user->roles->first();
            $roleIds = ['0', 0];
            if ($userRole) {
                $roleIds[] = $userRole->id;
                $roleIds[] = (string) $userRole->id;
            }

            // Mark HR-assigned docs as seen once the user opens Document page
            if (\Schema::hasColumn('ducument_uploads', 'assigned_seen')) {
                DucumentUpload::where('assigned_user_id', $userId)
                    ->where('assigned_seen', false)
                    ->update(['assigned_seen' => true]);
            }

            // Assigned / own uploads: match by user id (do not require created_by —
            // employee creatorId can differ from company id in some tenants).
            // Role-shared docs stay scoped to company.
            $documents = DucumentUpload::query()
                ->where(function ($q) use ($userId, $creatorId, $roleIds) {
                    $q->where('uploaded_by', $userId)
                        ->orWhere('assigned_user_id', $userId)
                        ->orWhere(function ($q2) use ($creatorId, $roleIds) {
                            $q2->where('created_by', $creatorId)
                                ->whereIn('role', $roleIds);
                        });
                })
                ->with(['uploader', 'assignedUser'])
                ->latest()
                ->get();
        }

        return view('documentUpload.index', compact('documents'));
    }

    public function create()
    {
        $roles = Role::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $roles->prepend('All', '0');

        $users = collect();
        $isHrViewer = $this->isHrViewer();
        if ($isHrViewer) {
            $users = $this->companyUsers();
            $users->prepend(__('Select User'), '');
        }

        return view('documentUpload.create', compact('roles', 'users', 'isHrViewer'));
    }

    public function store(Request $request)
    {
        $isHrViewer = $this->isHrViewer();

        $rules = [
            'name' => 'required',
            'documents' => 'required',
        ];
        if ($isHrViewer) {
            $rules['assigned_user_id'] = 'nullable|integer';
            if (empty($request->assigned_user_id)) {
                $rules['role'] = 'required';
            }
        }

        $validator = \Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        if ($isHrViewer && !empty($request->assigned_user_id)) {
            $validUser = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('user_id', $request->assigned_user_id)
                ->exists();
            if (!$validUser) {
                return redirect()->back()->with('error', __('Invalid user selected.'));
            }
        }

        $document = new DucumentUpload();
        $document->name = $request->name;
        $document->description = $request->description;
        $document->created_by = \Auth::user()->creatorId();
        $document->uploaded_by = \Auth::user()->id;

        if ($isHrViewer && !empty($request->assigned_user_id)) {
            // Assigned to one user + visible to HR (no role broadcast)
            $document->assigned_user_id = (int) $request->assigned_user_id;
            $document->role = '-1';
            $document->assigned_seen = false;
        } elseif ($isHrViewer) {
            $document->assigned_user_id = null;
            $document->role = $request->role;
            $document->assigned_seen = true;
        } else {
            // Employee upload: visible to self + HR only
            $document->assigned_user_id = null;
            $document->role = '-1';
            $document->assigned_seen = true;
        }

        if (!empty($request->documents)) {
            $image_size = $request->file('documents')->getSize();

            $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
            if ($result == 1) {
                $filenameWithExt = $request->file('documents')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('documents')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $dir = 'uploads/documentUpload/';

                $path = Utility::upload_file($request, 'documents', $fileNameToStore, $dir, []);
                $document->document = !empty($request->documents) ? $fileNameToStore : '';
                if ($path['flag'] == 1) {
                    // uploaded
                } else {
                    return redirect()->back()->with('error', __($path['msg']));
                }
            }
        }
        $document->save();

        return redirect()->route('document-upload.index')->with(
            'success',
            __('Document successfully uploaded.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '')
        );
    }

    public function show(DucumentUpload $ducumentUpload)
    {
        //
    }

    public function edit($id)
    {
        if (\Auth::user()->can('Edit Document')) {
            $roles = Role::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $roles->prepend('All', '0');

            $ducumentUpload = DucumentUpload::find($id);
            $users = $this->companyUsers();
            $users->prepend(__('Select User'), '');
            $isHrViewer = $this->isHrViewer();

            return view('documentUpload.edit', compact('roles', 'ducumentUpload', 'users', 'isHrViewer'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }

    public function update(Request $request, $id)
    {
        if (!\Auth::user()->can('Edit Document')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $isHrViewer = $this->isHrViewer();

        $validator = \Validator::make(
            $request->all(),
            [
                'name' => 'required',
                'role' => 'required',
                'assigned_user_id' => 'nullable|integer',
            ]
        );
        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }

        if ($isHrViewer && !empty($request->assigned_user_id)) {
            $validUser = Employee::where('created_by', \Auth::user()->creatorId())
                ->where('user_id', $request->assigned_user_id)
                ->exists();
            if (!$validUser) {
                return redirect()->back()->with('error', __('Invalid user selected.'));
            }
        }

        $document = DucumentUpload::find($id);
        $document->name = $request->name;
        $document->description = $request->description;
        if ($isHrViewer && !empty($request->assigned_user_id)) {
            $newAssignee = (int) $request->assigned_user_id;
            if ((int) $document->assigned_user_id !== $newAssignee) {
                $document->assigned_seen = false;
            }
            $document->assigned_user_id = $newAssignee;
            $document->role = '-1';
        } elseif ($isHrViewer) {
            $document->assigned_user_id = null;
            $document->role = $request->role;
            $document->assigned_seen = true;
        } else {
            $document->role = $request->role;
        }

        if (!empty($request->documents)) {
            $dir = 'uploads/documentUpload/';
            $file_path = $dir . $document->document;
            $image_size = $request->file('documents')->getSize();
            $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

            if ($result == 1) {
                Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                $filenameWithExt = $request->file('documents')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('documents')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $dir = 'uploads/documentUpload/';

                $path = Utility::upload_file($request, 'documents', $fileNameToStore, $dir, []);
                $document->document = !empty($request->documents) ? $fileNameToStore : '';
                if ($path['flag'] != 1) {
                    return redirect()->back()->with('error', __($path['msg']));
                }
            }
        }

        $document->save();

        return redirect()->route('document-upload.index')->with(
            'success',
            __('Document successfully uploaded.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '')
        );
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('Delete Document')) {
            $document = DucumentUpload::find($id);
            if ($document->created_by == \Auth::user()->creatorId()) {
                $document->delete();

                if (!empty($document->document)) {
                    $file_path = 'uploads/documentUpload/' . $document->document;
                    Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                }

                return redirect()->route('document-upload.index')->with('success', __('Document successfully deleted.'));
            }

            return redirect()->back()->with('error', __('Permission denied.'));
        }

        return redirect()->back()->with('error', __('Permission denied.'));
    }
}
