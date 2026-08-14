<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserRepository $users) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => $this->users->paginate(request()->integer('per_page', 15), request()->only(['q','role'])),
            'roles' => Role::pluck('name'),
            'filters' => request()->only(['q','role']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        $user->load('roles');
        return Inertia::render('Admin/Users/Edit', [
            'user'  => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'is_active' => $user->is_active,
                'role'      => $user->roles->first()?->name,
            ],
            'roles' => Role::pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());
        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot delete yourself.');
        $this->users->delete($user);
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'You cannot deactivate yourself.');
        $this->users->toggleActive($user);
        return back()->with('success', 'User status changed.');
    }
}
