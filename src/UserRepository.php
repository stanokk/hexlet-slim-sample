<?php

namespace App;

class UserRepository
{
    public function __construct()
    {
        session_start();
    }

    public function all()
    {
        return array_values($_SESSION);
    }

    public function find(string $id)
    {
        if (!isset($_SESSION[$id])) {
            throw new \Exception("Wrong user id: {$id}");
        }

        return $_SESSION[$id];
    }

    public function save(array $user)
    {
        if (!($user['id'])) {
            $user['id'] = uniqid();
        }
        $_SESSION[$user['id']] = $user;
        $file = 'people.json';
        $current = file_get_contents($file);
        empty($current) ? $updated = [] : $updated = json_decode($current, true);
        $updated[$user['id']] = $user;
        file_put_contents($file, json_encode($updated));
    }
}
