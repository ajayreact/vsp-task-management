<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\PersonalTodo;
use App\Modules\TaskManagement\Services\MyTodoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTodoController extends Controller
{
    public function __construct(protected MyTodoService $todos) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PersonalTodo::class);

        return Inertia::render('TaskManagement/todos/index', $this->todos->pagePayload($request->user(), [
            'tab' => $request->string('tab')->value(),
            'priority' => $request->string('priority')->value(),
            'date' => $request->string('date')->value(),
            'project' => $request->integer('project') ?: null,
            'client' => $request->integer('client') ?: null,
            'search' => $request->string('search')->trim()->value(),
        ]));
    }
}
