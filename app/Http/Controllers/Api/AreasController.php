<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Areas\UpdateAreaRequests;
use App\Http\Requests\Areas\StoreAreaRequests;
use App\Http\Resources\AreasResource;
use App\Models\Areas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Pipelines\FilterByName;
use App\Pipelines\FilterByState;
use Illuminate\Pipeline\Pipeline;
class AreasController extends Controller{
    public function index(Request $request){
        Gate::authorize('viewAny', Areas::class);
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $state = $request->input('state');
        $query = app(Pipeline::class)
            ->send(Areas::query())
            ->through([
                new FilterByName($search),
                new FilterByState($state),
            ])
            ->thenReturn();

        return AreasResource::collection($query->paginate($perPage));
    }
    public function store(StoreAreaRequests $request){
        Gate::authorize('create', Areas::class);
        $validated = $request->validated();
        $exists = Areas::whereRaw('LOWER(name) = ?', [$validated['name']])->exists();
        if ($exists) {
            return response()->json([
                'errors' => ['name' => ['Este nombre ya está registrado.']]
            ], 422);
        }
        $areas = Areas::create($validated);
        return response()->json([
            'state' => true,
            'message' => 'Areas registrado correctamente.',
            'areas' => $areas
        ]);
    }
    public function show(Areas $areas){
        Gate::authorize('view', $areas);
        return response()->json([
            'state' => true,
            'message' => 'Areas encontrado',
            'areas' => new AreasResource($areas),
        ], 200);
    }
    public function update(UpdateAreaRequests $request, Areas $areas){
        Gate::authorize('update', $areas);
        $validated = $request->validated();
        $exists = Areas::whereRaw('LOWER(name) = ?', [$validated['name']])
            ->where('id', '!=', $areas->id)
            ->exists();
        if ($exists) {
            return response()->json([
                'errors' => ['name' => ['Este nombre ya está registrado.']]
            ], 422);
        }
        $areas->update($validated);
        return response()->json([
            'state' => true,
            'message' => 'Almacén actualizado correctamente.',
            'areas' => $areas->refresh()
        ]);
    }
    public function destroy(Areas $areas){
        Gate::authorize('delete', $areas);
        $areas->delete();
        return response()->json([
            'state' => true,
            'message' => 'Areas eliminado de manera correcta',
        ]);
    }
}
