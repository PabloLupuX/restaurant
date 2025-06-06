<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeType;
use App\Http\Requests\Tipo_Empleado\StoreEmployeeTypeRequest;
use App\Http\Requests\Tipo_Empleado\UpdateEmployeeTypeRequest;
use App\Http\Resources\EmployeeTypeResource;
use App\Pipelines\FilterByName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Pipelines\FilterByState;
use Illuminate\Pipeline\Pipeline;

class EmployeeTypeController extends Controller{
    public function index(Request $request){
        Gate::authorize('viewAny', EmployeeType::class);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $state = $request->input('state');
        $query = app(Pipeline::class)
            ->send(EmployeeType::query())
            ->through([
                new FilterByName($search),
                new FilterByState($state),
            ])
            ->thenReturn();

        return EmployeeTypeResource::collection($query->paginate($perPage));
    }
    public function store(StoreEmployeeTypeRequest $request){
        Gate::authorize('create', EmployeeType::class);
        $validated = $request->validated();
        if (EmployeeType::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->exists()) {
            return response()->json([
                'errors' => ['name' => ['Este nombre ya está registrado.']]
            ], 422);
        }
        $tipoEmpleado = EmployeeType::create($validated);
        return response()->json([
            'message' => 'Tipo de empleado creado con éxito',
            'employeeType' => $tipoEmpleado,
        ]);
    }
    public function show(EmployeeType $employeeType){
        Gate::authorize('view', $employeeType);
        return response()->json([
            'state' => true,
            'message' => 'Tipo de empleado encontrado',
            'employeeType' => new EmployeeTypeResource($employeeType),
        ], 200);
    }
    public function update(UpdateEmployeeTypeRequest $request, EmployeeType $employeeType){
        Gate::authorize('update', $employeeType);
        $validated = $request->validated();
        $nameExists = EmployeeType::whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->where('id', '!=', $employeeType->id)
            ->exists();
        if ($nameExists) {
            return response()->json([
                'errors' => ['name' => ['Este nombre ya está registrado.']]
            ], 422);
        }
        $employeeType->update($validated);
        return response()->json([
            'state' => true,
            'message' => 'Tipo de empleado actualizado de manera correcta',
            'employeeType' => new EmployeeTypeResource($employeeType->refresh()),
        ]);
    }
    public function destroy(EmployeeType $employeeType){
        Gate::authorize('delete', $employeeType);
        $employeeType->delete();
        return response()->json([
            'state' => true,
            'message' => 'Tipo de empleado eliminado de manera correcta',
        ]);
    }
}
