<?php

namespace TheFramework\Http\Controllers;

use Exception;
use TheFramework\Http\Requests\UserRequest;
use TheFramework\Services\UserService;

class HomeController extends Controller
{
    private UserService $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function welcome()
    {
        return view('interface.welcome', [
            'title'        => 'THE FRAMEWORK - Modern PHP Framework with Database Migrations & REST API',
            'notification' => flash('notification'),
            'status'       => $this->userService->status(),
        ]);
    }

    public function users()
    {
        return view('interface.users', [
            'title'        => 'THE FRAMEWORK - User Management',
            'notification' => flash('notification'),
            'users'        => $this->userService->getAll(),
        ]);
    }

    public function informationUser($uid)
    {
        $user = $this->userService->getInformation($uid);

        return view('interface.detail', [
            'title'        => 'THE FRAMEWORK - ' . $user['name'] . ' - User Detail',
            'notification' => flash('notification'),
            'user'         => $user,
        ]);
    }

    public function createUser(UserRequest $request) {
        try {
            $this->userService->createUserService($request);
            return redirect('/users', 'success', 'User success created');
        } catch (Exception $e) {
            return redirect('/users', 'error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function updateUser($uid, UserRequest $request) {
        try {
            $this->userService->updateUserService($uid, $request);
            return redirect("/users/information/{$uid}", 'success', 'User updated successfully');
        } catch (Exception $e) {
            return redirect("/users/information/{$uid}", 'error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    
    public function deleteUser($uid) {
        try {
            $this->userService->deleteUserService($uid);
            return redirect("/users", 'success', 'User deleted successfully');
        } catch (Exception $e) {
            return redirect("/users", 'error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
